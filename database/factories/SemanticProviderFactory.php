<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\SemanticProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SemanticProvider>
 */
class SemanticProviderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->words(2, asText: true),
            'type' => fake()->randomElement(['internal', 'cube', 'dbt_semantic_layer']),
            'data_source_id' => null,
            'encrypted_config' => null,
            'capabilities' => [
                'supports_filters' => true,
            ],
        ];
    }
}
