<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\QueryCacheEntry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<QueryCacheEntry>
 */
class QueryCacheEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'cache_key' => fake()->unique()->sha256(),
            'result' => [
                'rows' => [
                    ['country' => 'US', 'revenue' => 1000],
                    ['country' => 'CA', 'revenue' => 500],
                ],
            ],
            'metadata' => [
                'duration_ms' => fake()->numberBetween(5, 1000),
            ],
            'expires_at' => Carbon::now()->addMinutes(5),
            'last_accessed_at' => null,
        ];
    }
}
