<?php

declare(strict_types=1);

namespace App\Analytics\DataSources\DTOs;

use JsonSerializable;

final readonly class TableCatalog implements JsonSerializable
{
    /**
     * @param  list<ColumnCatalog>  $columns
     */
    public function __construct(
        public string $schema,
        public string $name,
        public array $columns,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'schema' => $this->schema,
            'name' => $this->name,
            'columns' => $this->columns,
        ];
    }
}
