<?php

declare(strict_types=1);

namespace App\Analytics\Actions\SemanticModels;

use App\Models\Measure;
use App\Models\SemanticModel;

final readonly class AddMeasureToModel
{
    /**
     * @param  array{name: string, label: string, type: string, column?: string, expression?: array<string, mixed>, filters?: array<int, mixed>, format?: string, is_public?: bool, description?: string}  $payload
     */
    public function handle(SemanticModel $model, array $payload): Measure
    {
        return $model->measures()->create($payload);
    }
}
