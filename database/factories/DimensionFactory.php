<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Dimension;
use App\Models\SemanticModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dimension>
 */
class DimensionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'semantic_model_id' => SemanticModel::factory(),
            'name' => fake()->unique()->slug(2),
            'label' => fake()->words(2, asText: true),
            'description' => fake()->optional()->sentence(),
            'type' => fake()->randomElement(['string', 'number', 'boolean', 'time']),
            'column' => fake()->word(),
            'table_alias' => fake()->lexify('????'),
            'is_filterable' => true,
            'is_groupable' => true,
            'is_public' => true,
            'allowed_time_grains' => null,
        ];
    }
}
