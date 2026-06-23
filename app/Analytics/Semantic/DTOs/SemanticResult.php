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

    /**
     * Build a SemanticResult from raw DB rows. Columns are inferred from the
     * first row's keys (best effort) and tagged `unknown` type — semantic
     * column metadata comes from the query pipeline downstream.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, mixed>  $metadata
     */
    public static function fromRows(array $rows, array $metadata = []): self
    {
        $columns = [];

        if (count($rows) > 0) {
            foreach (array_keys($rows[0]) as $key) {
                $columns[] = ['key' => (string) $key, 'label' => (string) $key, 'type' => 'unknown'];
            }
        }

        return new self(columns: $columns, rows: $rows, metadata: $metadata);
    }

    public function jsonSerialize(): array
    {
        return [
            'columns' => $this->columns,
            'rows' => $this->rows,
            'metadata' => $this->metadata,
        ];
    }
}
