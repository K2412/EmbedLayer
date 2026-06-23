<?php

declare(strict_types=1);

namespace App\Analytics\Actions\SemanticModels;

use App\Models\Dimension;
use App\Models\SemanticModel;

final readonly class AddDimensionToModel
{
    /**
     * @param  array{name: string, label: string, type: string, column: string, table_alias?: string, is_filterable?: bool, is_groupable?: bool, is_public?: bool, allowed_time_grains?: list<string>, description?: string}  $payload
     */
    public function handle(SemanticModel $model, array $payload): Dimension
    {
        return $model->dimensions()->create($payload);
    }
}
