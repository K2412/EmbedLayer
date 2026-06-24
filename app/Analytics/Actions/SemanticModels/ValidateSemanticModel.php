<?php

declare(strict_types=1);

namespace App\Analytics\Actions\SemanticModels;

use App\Analytics\Support\Enums\MeasureType;
use App\Models\Measure;
use App\Models\SemanticModel;
use Illuminate\Support\Collection;

/**
 * Pre-execution structural check on a semantic model. Returns a list of
 * human-readable problems; an empty list means the model is structurally OK.
 *
 * NOT a full compile — the compiler raises its own errors for things like
 * unknown column types or unsupported time grains in a dialect.
 */
final readonly class ValidateSemanticModel
{
    /**
     * @return list<string>
     */
    public function handle(SemanticModel $model): array
    {
        $errors = [];

        $model->loadMissing(['measures', 'dimensions', 'joins', 'accessPolicies']);

        if ($model->measures->isEmpty()) {
            $errors[] = 'semantic model has no measures; at least one is required';
        }

        if ($model->base_table === null || $model->base_table === '') {
            $errors[] = 'semantic model is missing a base_table';
        }

        $errors = array_merge($errors, $this->checkUniqueNames($model->measures->pluck('name')->all(), 'measure'));
        $errors = array_merge($errors, $this->checkUniqueNames($model->dimensions->pluck('name')->all(), 'dimension'));

        $measureNames = $model->measures->keyBy('name');

        foreach ($model->measures as $measure) {
            $errors = array_merge($errors, $this->checkMeasure($measure, $measureNames));
        }

        foreach ($model->dimensions as $dimension) {
            if ($dimension->type === 'time' && empty($dimension->allowed_time_grains)) {
                $errors[] = "time dimension `{$dimension->name}` declares no allowed_time_grains";
            }
        }

        return $errors;
    }

    /**
     * @param  array<int, string|null>  $names
     * @return list<string>
     */
    private function checkUniqueNames(array $names, string $kind): array
    {
        $errors = [];
        $seen = [];

        foreach ($names as $name) {
            if ($name === null) {
                continue;
            }

            if (isset($seen[$name])) {
                $errors[] = "duplicate {$kind} name `{$name}`";

                continue;
            }

            $seen[$name] = true;
        }

        return $errors;
    }

    /**
     * @param  Collection<string, Measure>  $measuresByName
     * @return list<string>
     */
    private function checkMeasure(Measure $measure, $measuresByName): array
    {
        $errors = [];
        $type = MeasureType::tryFrom($measure->type);

        if ($type === null) {
            $errors[] = "measure `{$measure->name}` has unsupported type `{$measure->type}`";

            return $errors;
        }

        if ($type === MeasureType::Ratio) {
            $expr = $measure->expression ?? [];
            $referenced = [$expr['numerator'] ?? null, $expr['denominator'] ?? null];

            foreach ($referenced as $ref) {
                if ($ref === null) {
                    continue;
                }

                if (! $measuresByName->has($ref)) {
                    $errors[] = "ratio measure `{$measure->name}` references unknown measure `{$ref}`";
                }
            }
        }

        if ($type === MeasureType::Calculated) {
            $expr = $measure->expression ?? [];
            $referenced = $expr['measures'] ?? [];

            if (is_array($referenced)) {
                foreach ($referenced as $ref) {
                    if (is_string($ref) && ! $measuresByName->has($ref)) {
                        $errors[] = "calculated measure `{$measure->name}` references unknown measure `{$ref}`";
                    }
                }
            }
        }

        return $errors;
    }
}
