<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\SemanticModel;
use App\Models\SemanticProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SemanticModel>
 */
class SemanticModelFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $organization = Organization::factory();

        return [
            'organization_id' => $organization,
            'semantic_provider_id' => SemanticProvider::factory()->state(fn (array $attrs) => [
                'organization_id' => $attrs['organization_id'] ?? $organization,
            ]),
            'name' => fake()->unique()->slug(2),
            'label' => fake()->words(2, asText: true),
            'description' => fake()->optional()->sentence(),
            'base_table' => fake()->word(),
            'base_table_alias' => fake()->lexify('????'),
            'is_enabled' => true,
            'version' => 1,
        ];
    }
}
