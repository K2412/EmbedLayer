<?php

declare(strict_types=1);

namespace App\Analytics\Semantic\Providers;

use App\Analytics\Compiler\InternalQueryCompiler;
use App\Analytics\DataSources\Support\DataSourceConnectorRegistry;
use App\Analytics\Semantic\Contracts\SemanticProvider;
use App\Analytics\Semantic\DTOs\ProviderContext;
use App\Analytics\Semantic\DTOs\SemanticQuery;
use App\Analytics\Semantic\DTOs\SemanticResult;
use App\Models\SemanticModel;
use RuntimeException;

/**
 * Default semantic provider — compiles SemanticQueries against EmbedLayer's
 * own metadata catalogue and executes them through the matching
 * DataSourceConnector (Plan §8).
 */
final readonly class InternalSemanticProvider implements SemanticProvider
{
    public function __construct(
        private InternalQueryCompiler $compiler,
        private DataSourceConnectorRegistry $connectors,
    ) {}

    public function listModels(ProviderContext $context): array
    {
        $models = SemanticModel::query()
            ->where('organization_id', $context->organizationId)
            ->where('is_enabled', true)
            ->get();

        return [
            'models' => $models->map(fn (SemanticModel $model): array => [
                'name' => $model->name,
                'label' => $model->label,
                'description' => $model->description,
                'base_table' => $model->base_table,
                'version' => $model->version,
            ])->all(),
        ];
    }

    public function listFields(ProviderContext $context, string $modelName): array
    {
        $model = SemanticModel::query()
            ->where('organization_id', $context->organizationId)
            ->where('name', $modelName)
            ->firstOrFail();

        return [
            'measures' => $model->measures->where('is_public', true)->map(fn ($m): array => [
                'name' => $m->name,
                'label' => $m->label,
                'type' => $m->type,
                'format' => $m->format,
            ])->values()->all(),
            'dimensions' => $model->dimensions->where('is_public', true)->map(fn ($d): array => [
                'name' => $d->name,
                'label' => $d->label,
                'type' => $d->type,
                'allowed_time_grains' => $d->allowed_time_grains,
            ])->values()->all(),
        ];
    }

    public function run(ProviderContext $context, SemanticQuery $query): SemanticResult
    {
        $model = SemanticModel::query()
            ->where('organization_id', $context->organizationId)
            ->where('name', $query->model)
            ->where('is_enabled', true)
            ->first();

        if ($model === null) {
            throw new RuntimeException("Semantic model `{$query->model}` not found.");
        }

        $compiled = $this->compiler->compile($model, $query, $context);

        $provider = $model->semanticProvider;

        if ($provider === null || $provider->dataSource === null) {
            throw new RuntimeException("Semantic model `{$query->model}` is not bound to a data source.");
        }

        $connector = $this->connectors->for($provider->dataSource);

        $result = $connector->execute($compiled);

        return new SemanticResult(
            columns: $result->columns,
            rows: $result->rows,
            metadata: array_merge($result->metadata, [
                'compiled_dialect' => $compiled->dialect,
                'model' => $query->model,
            ]),
        );
    }

    public function capabilities(): array
    {
        return [
            'kind' => 'internal',
            'supports_joins' => true,
            'supports_filters' => true,
            'supports_time_grains' => true,
            'supported_time_grains' => ['day', 'week', 'month', 'quarter', 'year'],
        ];
    }
}
