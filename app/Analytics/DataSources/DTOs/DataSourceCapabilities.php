<?php

declare(strict_types=1);

namespace App\Analytics\DataSources\DTOs;

use JsonSerializable;

final readonly class DataSourceCapabilities implements JsonSerializable
{
    /**
     * @param  list<string>  $supportedTimeGrains
     */
    public function __construct(
        public bool $supportsSql,
        public bool $supportsJoins,
        public bool $supportsDateTrunc,
        public array $supportedTimeGrains,
        public string $dialect,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'supports_sql' => $this->supportsSql,
            'supports_joins' => $this->supportsJoins,
            'supports_date_trunc' => $this->supportsDateTrunc,
            'supported_time_grains' => $this->supportedTimeGrains,
            'dialect' => $this->dialect,
        ];
    }
}
