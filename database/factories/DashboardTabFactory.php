<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Dashboard;
use App\Models\DashboardTab;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DashboardTab>
 */
class DashboardTabFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dashboard_id' => Dashboard::factory(),
            'name' => fake()->words(2, asText: true),
            'slug' => fake()->unique()->slug(2),
            'position' => 0,
        ];
    }
}
