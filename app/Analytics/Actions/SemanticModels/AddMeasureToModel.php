<?php

declare(strict_types=1);

namespace App\Analytics\Actions\SemanticModels;

use App\Analytics\Support\Enums\MeasureType;
use App\Analytics\Support\Exceptions\SemanticModelValidationException;
use App\Models\Measure;
use App\Models\SemanticModel;

final readonly class AddMeasureToModel
{
    /**
     * @param  array{name: string, label: string, type: string, column?: string, expression?: array<string, mixed>, filters?: array<int, mixed>, format?: string, is_public?: bool, description?: string}  $payload
     */
    public function handle(SemanticModel $model, array $payload): Measure
    {
        $this->validate($payload);

        return $model->measures()->create($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function validate(array $payload): void
    {
        $errors = [];

        $type = MeasureType::tryFrom((string) ($payload['type'] ?? ''));

        if ($type === null) {
            $errors[] = 'measure type must be one of: '.implode(', ', MeasureType::values());
            throw new SemanticModelValidationException($errors);
        }

        if (! isset($payload['name']) || $payload['name'] === '') {
            $errors[] = 'measure name is required';
        }

        if ($type->requiresColumn() && empty($payload['column'])) {
            $errors[] = "measure type `{$type->value}` requires a column";
        }

        if ($type->requiresExpression() && empty($payload['expression'])) {
            $errors[] = "measure type `{$type->value}` requires an expression";
        }

        if ($type === MeasureType::Ratio && isset($payload['expression'])) {
            $expr = $payload['expression'];

            if (! is_array($expr) || ! isset($expr['numerator'], $expr['denominator'])) {
                $errors[] = 'ratio expression must define `numerator` and `denominator`';
            }
        }

        if ($errors !== []) {
            throw new SemanticModelValidationException($errors);
        }
    }
}
