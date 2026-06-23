<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AnalyticsJoin;
use App\Models\SemanticModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnalyticsJoin>
 */
class AnalyticsJoinFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'semantic_model_id' => SemanticModel::factory(),
            'name' => fake()->unique()->slug(2),
            'left_table_alias' => fake()->lexify('????'),
            'left_column' => fake()->word().'_id',
            'right_table' => fake()->word(),
            'right_table_alias' => fake()->lexify('????'),
            'right_column' => 'id',
            'type' => fake()->randomElement(['inner', 'left']),
            'relationship' => fake()->randomElement(['one_to_one', 'many_to_one', 'one_to_many']),
        ];
    }
}
