<?php

declare(strict_types=1);

namespace App\Analytics\DataSources\DTOs;

use JsonSerializable;

final readonly class SchemaCatalog implements JsonSerializable
{
    /**
     * @param  list<TableCatalog>  $tables
     */
    public function __construct(
        public array $tables,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'tables' => $this->tables,
        ];
    }
}
