<?php

declare(strict_types=1);

namespace App\Analytics\Compiler;

use App\Analytics\Compiler\Dialects\SqlDialect;
use App\Analytics\Compiler\Dialects\SqlDialectResolver;
use App\Analytics\Semantic\DTOs\Filter;
use App\Analytics\Semantic\DTOs\ProviderContext;
use App\Analytics\Semantic\DTOs\SemanticQuery;
use App\Analytics\Support\Enums\MeasureType;
use App\Analytics\Support\Exceptions\SemanticModelValidationException;
use App\Models\AnalyticsJoin;
use App\Models\DataSource;
use App\Models\Dimension;
use App\Models\Measure;
use App\Models\SemanticModel;
use RuntimeException;

/**
 * Deterministic SQL compiler for semantic queries against the EmbedLayer
 * internal provider (Plan §8.4). Operates from structured metadata only — no
 * SQL parsing, no string interpolation of user values. All literal data is
 * bound via the dialect's placeholder() output.
 */
final readonly class InternalQueryCompiler
{
    private const FILTER_OPERATORS = ['=', '!=', '<', '<=', '>', '>=', 'in', 'not_in'];

    public function __construct(
        private SqlDialectResolver $dialects,
        private FieldValidator $fieldValidator,
        private AccessPolicyCompiler $accessPolicyCompiler,
    ) {}

    public function compile(
        SemanticModel $model,
        SemanticQuery $query,
        ProviderContext $context,
    ): CompiledQuery {
        $dataSource = $this->resolveDataSource($model);
        $dialect = $this->dialects->forDataSource($dataSource);

        $this->fieldValidator->validate($model, $query);

        $measures = $this->indexMeasures($model);
        $dimensions = $this->indexDimensions($model);

        $selects = [];
        $groupBys = [];
        $bindings = [];
        $referencedAliases = [];

        foreach ($query->dimensions as $dimensionName) {
            $dimension = $dimensions[$dimensionName];
            $tableAlias = $dimension->table_alias ?? $model->base_table_alias;
            $expression = $this->qualifiedColumn($dialect, $tableAlias, $dimension->column);

            $selects[] = $expression.' AS '.$dialect->quoteIdentifier($dimension->name);
            $groupBys[] = $expression;

            $this->trackAlias($referencedAliases, $dimension->table_alias);
        }

        if ($query->timeDimension !== null) {
            $dimension = $dimensions[$query->timeDimension->name];
            $tableAlias = $dimension->table_alias ?? $model->base_table_alias;
            $rawExpression = $this->qualifiedColumn($dialect, $tableAlias, $dimension->column);
            $expression = $dialect->dateTrunc($query->timeDimension->grain, $rawExpression);

            $selects[] = $expression.' AS '.$dialect->quoteIdentifier($dimension->name);
            $groupBys[] = $expression;

            $this->trackAlias($referencedAliases, $dimension->table_alias);
        }

        foreach ($query->measures as $measureName) {
            $measure = $measures[$measureName];
            $expression = $this->compileMeasure($dialect, $measures, $measure, $model->base_table_alias);

            $selects[] = $expression.' AS '.$dialect->quoteIdentifier($measure->name);
        }

        // Track aliases referenced by filters so the right joins are emitted.
        foreach ($query->filters as $filter) {
            $field = $filter->field;
            if (isset($dimensions[$field])) {
                $this->trackAlias($referencedAliases, $dimensions[$field]->table_alias);
            }
        }

        $from = $model->base_table.' AS '.$dialect->quoteIdentifier($model->base_table_alias);

        $joinFragments = $this->compileRequiredJoins($dialect, $model, $referencedAliases);

        [$filterSql, $filterBindings] = $this->compileFilters(
            dialect: $dialect,
            model: $model,
            dimensions: $dimensions,
            measures: $measures,
            filters: $query->filters,
            context: $context,
        );

        $bindings = array_merge($bindings, $filterBindings);

        [$accessSql, $accessBindings] = $this->accessPolicyCompiler->compile(
            dialect: $dialect,
            model: $model,
            context: $context,
        );

        $bindings = array_merge($bindings, $accessBindings);

        $whereParts = array_values(array_filter([$filterSql, $accessSql], static fn (string $part): bool => $part !== ''));

        $orderBys = $this->compileOrderBy($dialect, $dimensions, $measures, $query, $groupBys, $selects);

        $sql = 'SELECT '.implode(', ', $selects)
            .' FROM '.$from
            .($joinFragments !== [] ? ' '.implode(' ', $joinFragments) : '')
            .($whereParts !== [] ? ' WHERE '.implode(' AND ', $whereParts) : '')
            .($groupBys !== [] ? ' GROUP BY '.implode(', ', $groupBys) : '')
            .($orderBys !== '' ? ' ORDER BY '.$orderBys : '')
            .$dialect->limitOffset($query->limit, $query->offset);

        return new CompiledQuery(
            sql: $sql,
            bindings: $bindings,
            dialect: $dialect->name(),
            metadata: [
                'model' => $model->name,
                'measures' => $query->measures,
                'dimensions' => $query->dimensions,
                'dialect' => $dialect->name(),
            ],
        );
    }

    private function qualifiedColumn(SqlDialect $dialect, string $tableAlias, string $column): string
    {
        return $dialect->quoteIdentifier($tableAlias).'.'.$dialect->quoteIdentifier($column);
    }

    /**
     * @param  array<string, Measure>  $measures
     */
    private function compileMeasure(SqlDialect $dialect, array $measures, Measure $measure, string $baseTableAlias): string
    {
        $type = $measure->type;

        if ($type === MeasureType::Calculated->value) {
            throw new RuntimeException('Calculated measures are unsupported in V1.');
        }

        if ($type === MeasureType::Ratio->value) {
            $expression = $measure->expression ?? [];
            $numeratorName = $expression['numerator'] ?? null;
            $denominatorName = $expression['denominator'] ?? null;

            if (! is_string($numeratorName) || ! is_string($denominatorName)) {
                throw new SemanticModelValidationException([
                    "Ratio measure `{$measure->name}` must declare `numerator` and `denominator` measure names.",
                ]);
            }

            if (! isset($measures[$numeratorName]) || ! isset($measures[$denominatorName])) {
                throw new SemanticModelValidationException([
                    "Ratio measure `{$measure->name}` references unknown component measure(s).",
                ]);
            }

            $numeratorExpr = $this->compileMeasure($dialect, $measures, $measures[$numeratorName], $baseTableAlias);
            $denominatorExpr = $this->compileMeasure($dialect, $measures, $measures[$denominatorName], $baseTableAlias);

            return "({$numeratorExpr}) / NULLIF(({$denominatorExpr}), 0)";
        }

        if ($type === MeasureType::Count->value && ($measure->column === null || $measure->column === '')) {
            return $dialect->aggregate('count', '*');
        }

        if ($measure->column === null || $measure->column === '') {
            throw new SemanticModelValidationException([
                "Measure `{$measure->name}` of type `{$type}` is missing a column.",
            ]);
        }

        $qualified = $this->qualifiedColumn($dialect, $baseTableAlias, $measure->column);

        return $dialect->aggregate($type, $qualified);
    }

    /**
     * @param  array<string, true>  $referencedAliases
     * @return list<string>
     */
    private function compileRequiredJoins(SqlDialect $dialect, SemanticModel $model, array $referencedAliases): array
    {
        if ($referencedAliases === []) {
            return [];
        }

        /** @var array<string, AnalyticsJoin> $joinsByAlias */
        $joinsByAlias = [];
        foreach ($model->joins as $join) {
            $joinsByAlias[$join->right_table_alias] = $join;
        }

        $fragments = [];

        foreach (array_keys($referencedAliases) as $alias) {
            if (! isset($joinsByAlias[$alias])) {
                continue;
            }

            $join = $joinsByAlias[$alias];
            $type = strtolower($join->type) === 'left' ? 'LEFT JOIN' : 'INNER JOIN';

            $left = $this->qualifiedColumn($dialect, $join->left_table_alias, $join->left_column);
            $right = $this->qualifiedColumn($dialect, $join->right_table_alias, $join->right_column);

            $fragments[] = $type
                .' '.$join->right_table
                .' AS '.$dialect->quoteIdentifier($join->right_table_alias)
                .' ON '.$left.' = '.$right;
        }

        return $fragments;
    }

    /**
     * @param  array<string, Dimension>  $dimensions
     * @param  array<string, Measure>  $measures
     * @param  list<Filter>  $filters
     * @return array{0: string, 1: list<scalar|null>}
     */
    private function compileFilters(
        SqlDialect $dialect,
        SemanticModel $model,
        array $dimensions,
        array $measures,
        array $filters,
        ProviderContext $context,
    ): array {
        if ($filters === []) {
            return ['', []];
        }

        $bindings = [];
        $fragments = [];

        foreach ($filters as $filter) {
            $operator = $filter->operator;

            if (! in_array($operator, self::FILTER_OPERATORS, true)) {
                throw new SemanticModelValidationException([
                    "Filter operator `{$operator}` is not supported.",
                ]);
            }

            $field = $filter->field;

            if (isset($dimensions[$field])) {
                $dimension = $dimensions[$field];
                $qualified = $this->qualifiedColumn(
                    $dialect,
                    $dimension->table_alias ?? $model->base_table_alias,
                    $dimension->column,
                );
            } elseif (isset($measures[$field])) {
                throw new SemanticModelValidationException([
                    "Filtering on measure `{$field}` is not supported in V1.",
                ]);
            } else {
                throw new SemanticModelValidationException([
                    "Filter references unknown field `{$field}` on model `{$model->name}`.",
                ]);
            }

            $value = $this->resolveFilterValue($filter, $context);

            if ($operator === 'in' || $operator === 'not_in') {
                if (! is_array($value)) {
                    $value = [$value];
                }

                if ($value === []) {
                    throw new SemanticModelValidationException([
                        "Filter on `{$field}` with operator `{$operator}` requires a non-empty value list.",
                    ]);
                }

                $placeholders = [];
                foreach (array_values($value) as $i => $v) {
                    $placeholders[] = $dialect->placeholder(count($bindings) + $i + 1);
                    $bindings[] = $this->normalizeScalar($v);
                }

                $sqlOp = $operator === 'in' ? 'IN' : 'NOT IN';
                $fragments[] = $qualified.' '.$sqlOp.' ('.implode(', ', $placeholders).')';

                continue;
            }

            $fragments[] = $qualified.' '.$operator.' '.$dialect->placeholder(count($bindings) + 1);
            $bindings[] = $this->normalizeScalar($value);
        }

        return [implode(' AND ', $fragments), $bindings];
    }

    private function resolveFilterValue(Filter $filter, ProviderContext $context): mixed
    {
        if ($filter->valueFromContext !== null) {
            $claim = $filter->valueFromContext;
            $claims = $context->claims;

            if (array_key_exists($claim, $claims)) {
                $value = $claims[$claim];

                if ($value !== null && $value !== '') {
                    return $value;
                }
            }

            $fallback = match ($claim) {
                'external_account_id' => $context->externalAccountId,
                'organization_id' => $context->organizationId,
                'project_id' => $context->projectId,
                'embed_id' => $context->embedId,
                default => null,
            };

            if ($fallback === null) {
                throw new SemanticModelValidationException([
                    "Filter on `{$filter->field}` requires context claim `{$claim}` which is missing.",
                ]);
            }

            return $fallback;
        }

        return $filter->value;
    }

    private function normalizeScalar(mixed $value): string|int|float|bool|null
    {
        if (is_scalar($value) || $value === null) {
            return $value;
        }

        return (string) json_encode($value);
    }

    /**
     * @param  array<string, Dimension>  $dimensions
     * @param  array<string, Measure>  $measures
     * @param  list<string>  $groupBys
     * @param  list<string>  $selects
     */
    private function compileOrderBy(
        SqlDialect $dialect,
        array $dimensions,
        array $measures,
        SemanticQuery $query,
        array $groupBys,
        array $selects,
    ): string {
        if ($query->orderBy === []) {
            return '';
        }

        $parts = [];
        foreach ($query->orderBy as $entry) {
            $field = $entry['field'] ?? null;
            $direction = strtoupper((string) ($entry['direction'] ?? 'asc'));
            if (! in_array($direction, ['ASC', 'DESC'], true)) {
                $direction = 'ASC';
            }
            if (! is_string($field) || $field === '') {
                continue;
            }
            $parts[] = $dialect->quoteIdentifier($field).' '.$direction;
        }

        return implode(', ', $parts);
    }

    /**
     * @return array<string, Measure>
     */
    private function indexMeasures(SemanticModel $model): array
    {
        $out = [];
        foreach ($model->measures as $measure) {
            $out[$measure->name] = $measure;
        }

        return $out;
    }

    /**
     * @return array<string, Dimension>
     */
    private function indexDimensions(SemanticModel $model): array
    {
        $out = [];
        foreach ($model->dimensions as $dimension) {
            $out[$dimension->name] = $dimension;
        }

        return $out;
    }

    /**
     * @param  array<string, true>  $aliases
     */
    private function trackAlias(array &$aliases, ?string $alias): void
    {
        if ($alias === null || $alias === '') {
            return;
        }

        $aliases[$alias] = true;
    }

    private function resolveDataSource(SemanticModel $model): DataSource
    {
        $provider = $model->semanticProvider;

        if ($provider === null) {
            throw new RuntimeException("Semantic model `{$model->name}` has no semantic provider.");
        }

        $dataSource = $provider->dataSource;

        if ($dataSource === null) {
            throw new RuntimeException("Semantic provider `{$provider->name}` is not bound to a data source.");
        }

        return $dataSource;
    }
}
