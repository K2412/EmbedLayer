<?php

declare(strict_types=1);

namespace App\Analytics\Semantic\DTOs;

use JsonSerializable;

final readonly class Filter implements JsonSerializable
{
    public function __construct(
        public string $field,
        public string $operator,
        public mixed $value = null,
        public ?string $valueFromContext = null,
    ) {}

    /**
     * @return array{field: string, operator: string, value?: mixed, value_from_context?: string}
     */
    public function jsonSerialize(): array
    {
        $payload = [
            'field' => $this->field,
            'operator' => $this->operator,
        ];

        if ($this->valueFromContext !== null) {
            $payload['value_from_context'] = $this->valueFromContext;
        } else {
            $payload['value'] = $this->value;
        }

        return $payload;
    }
}
