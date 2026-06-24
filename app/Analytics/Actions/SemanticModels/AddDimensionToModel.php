<?php

declare(strict_types=1);

namespace App\Analytics\Actions\SemanticModels;

use App\Analytics\Support\Enums\DimensionType;
use App\Analytics\Support\Enums\TimeGrain;
use App\Analytics\Support\Exceptions\SemanticModelValidationException;
use App\Models\Dimension;
use App\Models\SemanticModel;

final readonly class AddDimensionToModel
{
    /**
     * @param  array{name: string, label: string, type: string, column: string, table_alias?: string, is_filterable?: bool, is_groupable?: bool, is_public?: bool, allowed_time_grains?: list<string>, description?: string}  $payload
     */
    public function handle(SemanticModel $model, array $payload): Dimension
    {
        $this->validate($payload);

        return $model->dimensions()->create($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function validate(array $payload): void
    {
        $errors = [];

        $type = DimensionType::tryFrom((string) ($payload['type'] ?? ''));

        if ($type === null) {
            $errors[] = 'dimension type must be one of: '.implode(', ', DimensionType::values());
            throw new SemanticModelValidationException($errors);
        }

        if (empty($payload['name'])) {
            $errors[] = 'dimension name is required';
        }

        if (empty($payload['column'])) {
            $errors[] = 'dimension column is required';
        }

        if ($type === DimensionType::Time) {
            $grains = $payload['allowed_time_grains'] ?? [];

            if (! is_array($grains) || $grains === []) {
                $errors[] = 'time dimensions must declare at least one allowed_time_grains entry';
            } else {
                $validGrains = TimeGrain::values();

                foreach ($grains as $grain) {
                    if (! in_array($grain, $validGrains, true)) {
                        $errors[] = "unknown time grain `{$grain}`; allowed: ".implode(', ', $validGrains);
                    }
                }
            }
        } elseif (! empty($payload['allowed_time_grains'])) {
            $errors[] = 'allowed_time_grains is only valid for time dimensions';
        }

        if ($errors !== []) {
            throw new SemanticModelValidationException($errors);
        }
    }
}
