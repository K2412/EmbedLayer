<?php

declare(strict_types=1);

namespace App\Analytics\Actions\DataSources;

use App\Analytics\Security\CredentialVault;
use App\Models\DataSource;
use App\Models\Organization;
use SensitiveParameter;

final readonly class CreateDataSource
{
    public function __construct(private CredentialVault $vault) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public function handle(
        Organization $organization,
        string $name,
        string $driver,
        #[SensitiveParameter] array $config,
    ): DataSource {
        return DataSource::query()->create([
            'organization_id' => $organization->id,
            'name' => $name,
            'driver' => $driver,
            'encrypted_config' => $this->vault->encryptConfig($config),
        ]);
    }
}
