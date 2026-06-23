<?php

declare(strict_types=1);

namespace App\Analytics\Semantic\DTOs;

use JsonSerializable;

final readonly class SemanticQuery implements JsonSerializable
{
    /**
     * @param  list<string>  $measures
     * @param  list<string>  $dimensions
     * @param  list<Filter>  $filters
     * @param  list<array{field: string, direction: string}>  $orderBy
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public string $model,
        public array $measures,
        public array $dimensions = [],
        public ?TimeDimension $timeDimension = null,
        public array $filters = [],
        public array $orderBy = [],
        public ?int $limit = null,
        public ?int $offset = null,
        public array $context = [],
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'model' => $this->model,
            'measures' => $this->measures,
            'dimensions' => $this->dimensions,
            'time_dimension' => $this->timeDimension,
            'filters' => $this->filters,
            'order_by' => $this->orderBy,
            'limit' => $this->limit,
            'offset' => $this->offset,
            'context' => $this->context,
        ];
    }
}
