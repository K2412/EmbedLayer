<?php

namespace Database\Factories;

use App\Models\AnalyticsProject;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnalyticsProject>
 */
class AnalyticsProjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->words(2, asText: true),
        ];
    }
}
