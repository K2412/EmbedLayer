<?php

declare(strict_types=1);

namespace App\Analytics\Semantic\DTOs;

use JsonSerializable;

final readonly class SemanticResult implements JsonSerializable
{
    /**
     * @param  list<array{key: string, label: string, type: string}>  $columns
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public array $columns,
        public array $rows,
        public array $metadata = [],
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'columns' => $this->columns,
            'rows' => $this->rows,
            'metadata' => $this->metadata,
        ];
    }
}
