<?php

declare(strict_types=1);

namespace App\Analytics\DataSources\DTOs;

use JsonSerializable;

final readonly class ConnectionTestResult implements JsonSerializable
{
    public function __construct(
        public bool $success,
        public ?string $errorMessage = null,
    ) {}

    public static function success(): self
    {
        return new self(success: true);
    }

    public static function failed(string $message): self
    {
        return new self(success: false, errorMessage: $message);
    }

    public function jsonSerialize(): array
    {
        return [
            'success' => $this->success,
            'error_message' => $this->errorMessage,
        ];
    }
}
