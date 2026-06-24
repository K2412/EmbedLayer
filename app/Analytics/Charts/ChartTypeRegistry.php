<?php

declare(strict_types=1);

namespace App\Analytics\Charts;

use App\Analytics\Support\Exceptions\SemanticModelValidationException;

/**
 * V1 chart-type catalog. Keys mirror the values stored in
 * analytics_charts.chart_type. Plan §5 dashboard builder.
 */
final class ChartTypeRegistry
{
    /** @var array<string, ChartType>|null */
    private static ?array $cache = null;

    /**
     * @return array<string, ChartType>
     */
    public function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $types = [
            new ChartType(
                key: 'number_card',
                label: 'Number card',
                minMeasures: 1, maxMeasures: 1,
                minDimensions: 0, maxDimensions: 0,
            ),
            new ChartType(
                key: 'bar_chart',
                label: 'Bar chart',
                minMeasures: 1, maxMeasures: null,
                minDimensions: 1, maxDimensions: 2,
            ),
            new ChartType(
                key: 'line_chart',
                label: 'Line chart',
                minMeasures: 1, maxMeasures: null,
                minDimensions: 0, maxDimensions: 1,
                requiresTimeDimension: true,
            ),
            new ChartType(
                key: 'table',
                label: 'Table',
                minMeasures: 0, maxMeasures: null,
                minDimensions: 0, maxDimensions: null,
            ),
        ];

        return self::$cache = array_combine(
            array_map(static fn (ChartType $t): string => $t->key, $types),
            $types,
        );
    }

    public function get(string $key): ChartType
    {
        $types = $this->all();

        if (! isset($types[$key])) {
            throw new SemanticModelValidationException([
                "unknown chart_type `{$key}`; allowed: ".implode(', ', array_keys($types)),
            ]);
        }

        return $types[$key];
    }

    /** @return list<string> */
    public function keys(): array
    {
        return array_keys($this->all());
    }
}
