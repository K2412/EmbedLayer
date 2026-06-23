<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Chart;
use App\Models\Dashboard;
use App\Models\SemanticModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Chart>
 */
class ChartFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dashboard_id' => Dashboard::factory(),
            'dashboard_tab_id' => null,
            'semantic_model_id' => SemanticModel::factory(),
            'name' => fake()->words(2, asText: true),
            'description' => fake()->optional()->sentence(),
            'chart_type' => fake()->randomElement(['number_card', 'bar_chart', 'line_chart', 'table']),
            'options' => null,
        ];
    }
}
