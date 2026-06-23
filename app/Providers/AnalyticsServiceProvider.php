<?php

declare(strict_types=1);

namespace App\Providers;

use App\Analytics\DataSources\Support\AnalyticsConnectionFactory;
use App\Analytics\DataSources\Support\DataSourceConnectorRegistry;
use App\Analytics\Security\CredentialVault;
use Illuminate\Support\ServiceProvider;

/**
 * Centralizes DI wiring for the EmbedLayer analytics layer.
 */
final class AnalyticsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CredentialVault::class, fn () => CredentialVault::fromConfig());
        $this->app->singleton(AnalyticsConnectionFactory::class);
        $this->app->singleton(DataSourceConnectorRegistry::class);
    }
}
