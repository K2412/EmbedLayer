<?php

declare(strict_types=1);

namespace App\Analytics\Actions\SemanticModels;

use App\Models\SemanticModel;
use App\Models\SemanticProvider;

final readonly class CreateSemanticModel
{
    public function handle(
        SemanticProvider $provider,
        string $name,
        string $label,
        ?string $baseTable = null,
        ?string $baseTableAlias = null,
    ): SemanticModel {
        return SemanticModel::query()->create([
            'organization_id' => $provider->organization_id,
            'semantic_provider_id' => $provider->id,
            'name' => $name,
            'label' => $label,
            'base_table' => $baseTable,
            'base_table_alias' => $baseTableAlias,
        ]);
    }
}
