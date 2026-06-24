<?php

declare(strict_types=1);

namespace App\Analytics\Actions\SemanticModels;

use App\Analytics\Support\Exceptions\SemanticModelValidationException;
use App\Models\AccessPolicy;
use App\Models\SemanticModel;

/**
 * Adds an access policy to a semantic model. V1 only allows
 * `value_from_context` rules — literal values are forbidden so embed token
 * claims always drive tenant scoping (Plan §4.8 + §17.2).
 */
final readonly class AddAccessPolicy
{
    private const ALLOWED_OPERATORS = ['=', '!=', '<', '<=', '>', '>=', 'in', 'not_in'];

    /**
     * @param  array{name: string, is_required?: bool, rules: list<array{field: string, operator?: string, value_from_context: string}>}  $payload
     */
    public function handle(SemanticModel $model, array $payload): AccessPolicy
    {
        $this->validate($payload);

        return $model->accessPolicies()->create($payload)->refresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function validate(array $payload): void
    {
        $errors = [];

        if (empty($payload['name'])) {
            $errors[] = 'access policy name is required';
        }

        $rules = $payload['rules'] ?? null;

        if (! is_array($rules) || $rules === []) {
            $errors[] = 'access policy must declare at least one rule';
            throw new SemanticModelValidationException($errors);
        }

        foreach ($rules as $i => $rule) {
            if (! is_array($rule)) {
                $errors[] = "rule #{$i} must be an object";

                continue;
            }

            if (empty($rule['field'])) {
                $errors[] = "rule #{$i} is missing `field`";
            }

            if (! array_key_exists('value_from_context', $rule) || $rule['value_from_context'] === '') {
                $errors[] = "rule #{$i} must declare `value_from_context`; literal `value` is forbidden in V1";
            }

            if (array_key_exists('value', $rule)) {
                $errors[] = "rule #{$i} has a literal `value`; V1 only allows `value_from_context`";
            }

            $operator = $rule['operator'] ?? '=';

            if (! in_array($operator, self::ALLOWED_OPERATORS, true)) {
                $errors[] = "rule #{$i} uses unsupported operator `{$operator}`";
            }
        }

        if ($errors !== []) {
            throw new SemanticModelValidationException($errors);
        }
    }
}
