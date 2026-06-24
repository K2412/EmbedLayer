<?php

declare(strict_types=1);

namespace App\Analytics\Actions\SemanticModels;

use App\Analytics\Support\Enums\JoinRelationship;
use App\Analytics\Support\Exceptions\SemanticModelValidationException;
use App\Models\AnalyticsJoin;
use App\Models\SemanticModel;

final readonly class AddJoinToModel
{
    /**
     * @param  array{name: string, left_table_alias: string, left_column: string, right_table: string, right_table_alias: string, right_column: string, type?: string, relationship: string}  $payload
     */
    public function handle(SemanticModel $model, array $payload): AnalyticsJoin
    {
        $this->validate($payload);

        return $model->joins()->create($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function validate(array $payload): void
    {
        $errors = [];

        $relationship = JoinRelationship::tryFrom((string) ($payload['relationship'] ?? ''));

        if ($relationship === null) {
            $errors[] = 'join relationship must be one of: '.implode(', ', JoinRelationship::values());
        }

        foreach (['name', 'left_table_alias', 'left_column', 'right_table', 'right_table_alias', 'right_column'] as $field) {
            if (empty($payload[$field])) {
                $errors[] = "join field `{$field}` is required";
            }
        }

        $type = $payload['type'] ?? 'left';

        if (! in_array($type, ['inner', 'left'], true)) {
            $errors[] = "join type must be `inner` or `left`, got `{$type}`";
        }

        if ($errors !== []) {
            throw new SemanticModelValidationException($errors);
        }
    }
}
