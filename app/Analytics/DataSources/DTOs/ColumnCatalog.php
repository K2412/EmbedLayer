<?php

declare(strict_types=1);

namespace App\Analytics\DataSources\DTOs;

use JsonSerializable;

final readonly class ColumnCatalog implements JsonSerializable
{
    public function __construct(
        public string $name,
        public string $dataType,
        public bool $nullable = true,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'data_type' => $this->dataType,
            'nullable' => $this->nullable,
        ];
    }
}
