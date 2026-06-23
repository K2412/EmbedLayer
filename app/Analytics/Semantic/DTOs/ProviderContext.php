<?php

declare(strict_types=1);

namespace App\Analytics\Semantic\DTOs;

use JsonSerializable;

final readonly class ProviderContext implements JsonSerializable
{
    /**
     * @param  array<string, mixed>  $claims
     */
    public function __construct(
        public string $organizationId,
        public string $projectId,
        public ?string $externalAccountId = null,
        public ?string $embedId = null,
        public array $claims = [],
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'organization_id' => $this->organizationId,
            'project_id' => $this->projectId,
            'external_account_id' => $this->externalAccountId,
            'embed_id' => $this->embedId,
            'claims' => $this->claims,
        ];
    }
}
