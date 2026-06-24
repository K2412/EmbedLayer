<?php

declare(strict_types=1);

namespace App\Analytics\Charts;

use App\Analytics\Semantic\DTOs\SemanticResult;

/**
 * Declarative description of a chart type's shape requirements. Used by
 * the dashboard builder to validate that a SemanticQuery can be rendered
 * as the chosen chart and by the runtime to pick the right adapter.
 */
final readonly class ChartType
{
    public function __construct(
        public string $key,
        public string $label,
        public int $minMeasures,
        public ?int $maxMeasures,
        public int $minDimensions,
        public ?int $maxDimensions,
        public bool $requiresTimeDimension = false,
    ) {}

    /**
     * Returns a list of human-readable problems, empty if the result fits
     * this chart type. Use AFTER FieldValidator so we know fields exist.
     *
     * @return list<string>
     */
    public function validateShape(int $measureCount, int $dimensionCount, bool $hasTimeDimension): array
    {
        $errors = [];

        if ($measureCount < $this->minMeasures) {
            $errors[] = "chart `{$this->key}` requires at least {$this->minMeasures} measure(s); got {$measureCount}";
        }

        if ($this->maxMeasures !== null && $measureCount > $this->maxMeasures) {
            $errors[] = "chart `{$this->key}` accepts at most {$this->maxMeasures} measure(s); got {$measureCount}";
        }

        if ($dimensionCount < $this->minDimensions) {
            $errors[] = "chart `{$this->key}` requires at least {$this->minDimensions} dimension(s); got {$dimensionCount}";
        }

        if ($this->maxDimensions !== null && $dimensionCount > $this->maxDimensions) {
            $errors[] = "chart `{$this->key}` accepts at most {$this->maxDimensions} dimension(s); got {$dimensionCount}";
        }

        if ($this->requiresTimeDimension && ! $hasTimeDimension) {
            $errors[] = "chart `{$this->key}` requires a time dimension";
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    public function validateResult(SemanticResult $result): array
    {
        return $this->validateShape(
            measureCount: count($result->columns),
            dimensionCount: 0, // result columns are flattened; shape check happens pre-execute
            hasTimeDimension: false,
        );
    }
}
