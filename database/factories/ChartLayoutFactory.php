<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Chart;
use App\Models\ChartLayout;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChartLayout>
 */
class ChartLayoutFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chart_id' => Chart::factory(),
            'dashboard_tab_id' => null,
            'grid_x' => fake()->numberBetween(0, 11),
            'grid_y' => fake()->numberBetween(0, 11),
            'grid_w' => fake()->numberBetween(1, 12),
            'grid_h' => fake()->numberBetween(1, 12),
        ];
    }
}
