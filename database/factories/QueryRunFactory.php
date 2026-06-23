<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\QueryRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QueryRun>
 */
class QueryRunFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'semantic_provider_id' => null,
            'dashboard_id' => null,
            'chart_id' => null,
            'provider_type' => fake()->randomElement(['internal', 'cube', 'dbt_semantic_layer']),
            'model_name' => fake()->slug(2),
            'status' => fake()->randomElement(['ok', 'error', 'timeout']),
            'duration_ms' => fake()->numberBetween(5, 5000),
            'cache_hit' => false,
            'cache_key' => null,
            'external_account_id' => null,
            'query_shape' => [
                'measures' => ['revenue'],
                'dimensions' => ['country'],
            ],
            'error_message' => null,
        ];
    }
}
