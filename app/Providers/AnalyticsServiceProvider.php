<?php

declare(strict_types=1);

namespace App\Providers;

use App\Analytics\Security\CredentialVault;
use Illuminate\Support\ServiceProvider;

/**
 * Centralizes DI wiring for the EmbedLayer analytics layer. Concrete
 * connector/provider bindings land in later milestones; for now this just
 * holds the CredentialVault singleton.
 */
final class AnalyticsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CredentialVault::class, fn () => CredentialVault::fromConfig());
    }
}
