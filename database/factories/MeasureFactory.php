<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Measure;
use App\Models\SemanticModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Measure>
 */
class MeasureFactory extends Factory
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
            'type' => fake()->randomElement(['count', 'count_distinct', 'sum', 'avg', 'min', 'max', 'ratio', 'calculated']),
            'column' => fake()->word(),
            'expression' => null,
            'filters' => null,
            'format' => fake()->randomElement(['currency', 'percent', 'number', 'duration']),
            'is_public' => true,
        ];
    }
}
