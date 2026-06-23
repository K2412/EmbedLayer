<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Chart;
use App\Models\ChartQuery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChartQuery>
 */
class ChartQueryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chart_id' => Chart::factory(),
            'semantic_query' => [
                'measures' => ['revenue'],
                'dimensions' => ['country'],
                'filters' => [],
                'time_range' => 'last_30_days',
            ],
        ];
    }
}
