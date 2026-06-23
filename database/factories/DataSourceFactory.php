<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DataSource;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DataSource>
 */
class DataSourceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->words(2, asText: true),
            'driver' => fake()->randomElement(['postgres', 'mysql', 'bigquery', 'snowflake', 'clickhouse']),
            'encrypted_config' => [
                'host' => fake()->domainName(),
                'port' => fake()->numberBetween(1024, 65535),
                'database' => fake()->word(),
            ],
            'capabilities' => [
                'supports_window_functions' => true,
                'supports_ctes' => true,
            ],
            'last_introspected_schema' => null,
            'last_tested_at' => null,
            'last_introspected_at' => null,
        ];
    }
}
