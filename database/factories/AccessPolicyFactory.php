<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AccessPolicy;
use App\Models\SemanticModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccessPolicy>
 */
class AccessPolicyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'semantic_model_id' => SemanticModel::factory(),
            'name' => fake()->words(2, asText: true),
            'rules' => [
                'filter' => [
                    'column' => 'tenant_id',
                    'op' => '=',
                    'value' => '{{ external_account_id }}',
                ],
            ],
            'is_required' => true,
        ];
    }
}
