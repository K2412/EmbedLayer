<?php

declare(strict_types=1);

namespace App\Analytics\Compiler;

use JsonSerializable;

final readonly class CompiledQuery implements JsonSerializable
{
    /**
     * @param  list<scalar|null>  $bindings
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $sql,
        public array $bindings,
        public string $dialect,
        public array $metadata = [],
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'sql' => $this->sql,
            'bindings' => $this->bindings,
            'dialect' => $this->dialect,
            'metadata' => $this->metadata,
        ];
    }
}
