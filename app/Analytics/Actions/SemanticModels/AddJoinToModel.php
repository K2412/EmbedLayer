<?php

declare(strict_types=1);

namespace App\Analytics\Actions\SemanticModels;

use App\Models\AnalyticsJoin;
use App\Models\SemanticModel;

final readonly class AddJoinToModel
{
    /**
     * @param  array{name: string, left_table_alias: string, left_column: string, right_table: string, right_table_alias: string, right_column: string, type?: string, relationship: string}  $payload
     */
    public function handle(SemanticModel $model, array $payload): AnalyticsJoin
    {
        return $model->joins()->create($payload);
    }
}
