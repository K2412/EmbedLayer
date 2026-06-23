<?php

declare(strict_types=1);

namespace App\Analytics\Semantic\DTOs;

use JsonSerializable;

final readonly class TimeDimension implements JsonSerializable
{
    public function __construct(
        public string $name,
        public string $grain,
    ) {}

    /**
     * @return array{name: string, grain: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'grain' => $this->grain,
        ];
    }
}
