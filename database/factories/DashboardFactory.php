<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AnalyticsProject;
use App\Models\Dashboard;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dashboard>
 */
class DashboardFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $organization = Organization::factory();

        return [
            'organization_id' => $organization,
            'analytics_project_id' => AnalyticsProject::factory()->state(fn (array $attrs) => [
                'organization_id' => $attrs['organization_id'] ?? $organization,
            ]),
            'name' => fake()->words(3, asText: true),
            'slug' => fake()->unique()->slug(2),
            'description' => fake()->optional()->sentence(),
            'theme' => null,
            'default_filters' => null,
            'is_published' => false,
            'published_at' => null,
        ];
    }
}
