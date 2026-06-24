<?php

declare(strict_types=1);

namespace App\Analytics\Compiler;

use App\Analytics\Compiler\Dialects\SqlDialect;
use App\Analytics\Semantic\DTOs\ProviderContext;
use App\Analytics\Support\Exceptions\SemanticModelValidationException;
use App\Models\AccessPolicy;
use App\Models\SemanticModel;

/**
 * Compiles required {@see AccessPolicy} rows into a SQL WHERE fragment plus a
 * bindings list. Every rule MUST resolve its value from the
 * {@see ProviderContext::$claims} map — literal values are not permitted
 * server-side (Plan §4.8, §17.2).
 *
 * Each rule's `field` references the physical column on the model's base
 * table. The qualified column is produced via `base_table_alias.field`
 * unless the rule supplies an explicit `table_alias`.
 */
final class AccessPolicyCompiler
{
    private const ALLOWED_OPERATORS = ['=', '!=', '<', '<=', '>', '>=', 'in', 'not_in'];

    /**
     * @return array{0: string, 1: list<scalar|null>}
     */
    public function compile(
        SqlDialect $dialect,
        SemanticModel $model,
        ProviderContext $context,
    ): array {
        $policies = $model->accessPolicies()->where('is_required', true)->get();

        if ($policies->isEmpty()) {
            return ['', []];
        }

        $policyFragments = [];
        $bindings = [];
        $errors = [];

        /** @var AccessPolicy $policy */
        foreach ($policies as $policy) {
            $rules = $policy->rules;

            if (! is_array($rules) || $rules === []) {
                $errors[] = "Access policy `{$policy->name}` on model `{$model->name}` has no rules.";

                continue;
            }

            $ruleFragments = [];

            foreach ($rules as $index => $rule) {
                if (! is_array($rule)) {
                    $errors[] = "Access policy `{$policy->name}` rule #{$index} must be an object.";

                    continue;
                }

                $field = $rule['field'] ?? null;
                $operator = $rule['operator'] ?? '=';
                $claim = $rule['value_from_context'] ?? null;
                $tableAlias = $rule['table_alias'] ?? $model->base_table_alias;

                if (! is_string($field) || $field === '') {
                    $errors[] = "Access policy `{$policy->name}` rule #{$index} is missing `field`.";

                    continue;
                }

                if (! in_array($operator, self::ALLOWED_OPERATORS, true)) {
                    $errors[] = "Access policy `{$policy->name}` rule #{$index} uses unsupported operator `{$operator}`.";

                    continue;
                }

                if (! is_string($claim) || $claim === '') {
                    $errors[] = "Access policy `{$policy->name}` rule #{$index} must declare `value_from_context`.";

                    continue;
                }

                $value = $this->resolveClaim($context, $claim);

                if ($value === null) {
                    $errors[] = "Access policy `{$policy->name}` requires context claim `{$claim}` which is missing.";

                    continue;
                }

                $qualified = $dialect->quoteIdentifier((string) $tableAlias)
                    .'.'
                    .$dialect->quoteIdentifier($field);

                if ($operator === 'in' || $operator === 'not_in') {
                    if (! is_array($value)) {
                        $value = [$value];
                    }

                    $placeholders = [];
                    foreach (array_values($value) as $i => $v) {
                        $placeholders[] = $dialect->placeholder(count($bindings) + $i + 1);
                        $bindings[] = $this->normalizeScalar($v);
                    }

                    $sqlOp = $operator === 'in' ? 'IN' : 'NOT IN';
                    $ruleFragments[] = $qualified.' '.$sqlOp.' ('.implode(', ', $placeholders).')';

                    continue;
                }

                $ruleFragments[] = $qualified.' '.$operator.' '.$dialect->placeholder(count($bindings) + 1);
                $bindings[] = $this->normalizeScalar($value);
            }

            if ($ruleFragments !== []) {
                $policyFragments[] = '('.implode(' AND ', $ruleFragments).')';
            }
        }

        if ($errors !== []) {
            throw new SemanticModelValidationException($errors);
        }

        if ($policyFragments === []) {
            return ['', []];
        }

        return [implode(' AND ', $policyFragments), $bindings];
    }

    private function resolveClaim(ProviderContext $context, string $claim): mixed
    {
        $claims = $context->claims;

        if (array_key_exists($claim, $claims) && $claims[$claim] !== null && $claims[$claim] !== '') {
            return $claims[$claim];
        }

        // Allow first-class context fields as fallback claim sources.
        return match ($claim) {
            'external_account_id' => $context->externalAccountId,
            'organization_id' => $context->organizationId,
            'project_id' => $context->projectId,
            'embed_id' => $context->embedId,
            default => null,
        };
    }

    private function normalizeScalar(mixed $value): string|int|float|bool|null
    {
        if (is_scalar($value) || $value === null) {
            return $value;
        }

        return (string) json_encode($value);
    }
}
