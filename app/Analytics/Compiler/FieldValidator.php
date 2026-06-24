<?php

declare(strict_types=1);

namespace App\Analytics\Compiler;

use App\Analytics\Semantic\DTOs\SemanticQuery;
use App\Analytics\Support\Enums\DimensionType;
use App\Analytics\Support\Exceptions\SemanticModelValidationException;
use App\Models\Dimension;
use App\Models\Measure;
use App\Models\SemanticModel;

/**
 * Validates that every field referenced by a SemanticQuery exists on the model,
 * is public, and (for time dimensions) supports the requested grain.
 *
 * Throws {@see SemanticModelValidationException} with the full error list rather
 * than failing fast — callers usually want to surface every problem at once.
 */
final class FieldValidator
{
    public function validate(SemanticModel $model, SemanticQuery $query): void
    {
        $measures = $this->loadMeasures($model);
        $dimensions = $this->loadDimensions($model);

        $errors = [];

        foreach ($query->measures as $measureName) {
            if (! isset($measures[$measureName])) {
                $errors[] = "Unknown measure `{$measureName}` on model `{$model->name}`.";

                continue;
            }

            if (! $measures[$measureName]->is_public) {
                $errors[] = "Measure `{$measureName}` on model `{$model->name}` is not public.";
            }
        }

        foreach ($query->dimensions as $dimensionName) {
            if (! isset($dimensions[$dimensionName])) {
                $errors[] = "Unknown dimension `{$dimensionName}` on model `{$model->name}`.";

                continue;
            }

            if (! $dimensions[$dimensionName]->is_public) {
                $errors[] = "Dimension `{$dimensionName}` on model `{$model->name}` is not public.";
            }
        }

        foreach ($query->filters as $filter) {
            $field = $filter->field;

            if (! isset($dimensions[$field]) && ! isset($measures[$field])) {
                $errors[] = "Filter references unknown field `{$field}` on model `{$model->name}`.";

                continue;
            }

            if (isset($dimensions[$field]) && ! $dimensions[$field]->is_public) {
                $errors[] = "Filter field `{$field}` on model `{$model->name}` is not public.";
            }

            if (isset($measures[$field]) && ! $measures[$field]->is_public) {
                $errors[] = "Filter field `{$field}` on model `{$model->name}` is not public.";
            }
        }

        if ($query->timeDimension !== null) {
            $name = $query->timeDimension->name;
            $grain = $query->timeDimension->grain;

            if (! isset($dimensions[$name])) {
                $errors[] = "Time dimension `{$name}` is not declared on model `{$model->name}`.";
            } else {
                $dimension = $dimensions[$name];

                if ($dimension->type !== DimensionType::Time->value) {
                    $errors[] = "Dimension `{$name}` on model `{$model->name}` is not a time dimension.";
                }

                $allowed = $dimension->allowed_time_grains;

                if (is_array($allowed) && ! in_array($grain, $allowed, true)) {
                    $errors[] = "Time grain `{$grain}` is not allowed for dimension `{$name}` on model `{$model->name}`.";
                }
            }
        }

        if ($errors !== []) {
            throw new SemanticModelValidationException($errors);
        }
    }

    /**
     * @return array<string, Measure>
     */
    private function loadMeasures(SemanticModel $model): array
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
    private function loadDimensions(SemanticModel $model): array
    {
        $out = [];
        foreach ($model->dimensions as $dimension) {
            $out[$dimension->name] = $dimension;
        }

        return $out;
    }
}
