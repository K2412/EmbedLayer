<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Dashboard;
use App\Models\Embed;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Embed>
 */
class EmbedFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $organization = Organization::factory();

        return [
            'organization_id' => $organization,
            'dashboard_id' => Dashboard::factory()->state(fn (array $attrs) => [
                'organization_id' => $attrs['organization_id'] ?? $organization,
            ]),
            'name' => fake()->words(2, asText: true),
            'default_ttl_seconds' => 300,
            'theme' => null,
            'default_filters' => null,
            'is_enabled' => true,
        ];
    }
}
