<?php

declare(strict_types=1);

namespace App\Providers;

use App\Analytics\Charts\ChartTypeRegistry;
use App\Analytics\Compiler\AccessPolicyCompiler;
use App\Analytics\Compiler\Dialects\SqlDialectResolver;
use App\Analytics\Compiler\FieldValidator;
use App\Analytics\Compiler\InternalQueryCompiler;
use App\Analytics\DataSources\Support\AnalyticsConnectionFactory;
use App\Analytics\DataSources\Support\DataSourceConnectorRegistry;
use App\Analytics\Embeds\EmbedTokenManager;
use App\Analytics\Pipelines\Pipes\ApplyAccessPolicies;
use App\Analytics\Pipelines\Pipes\CheckCache;
use App\Analytics\Pipelines\Pipes\ExecuteQuery;
use App\Analytics\Pipelines\Pipes\NormalizeResult;
use App\Analytics\Pipelines\Pipes\RecordQueryRun;
use App\Analytics\Pipelines\Pipes\ResolveModel;
use App\Analytics\Pipelines\Pipes\StoreResultCache;
use App\Analytics\Pipelines\Pipes\ValidateQueryPermissions;
use App\Analytics\Pipelines\QueryExecutionPipeline;
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

        $this->app->singleton(SqlDialectResolver::class);
        $this->app->singleton(FieldValidator::class);
        $this->app->singleton(AccessPolicyCompiler::class);
        $this->app->singleton(InternalQueryCompiler::class);
        $this->app->singleton(ChartTypeRegistry::class);

        $this->app->singleton(EmbedTokenManager::class, fn () => new EmbedTokenManager(
            signingKey: (string) config('embedlayer.embed_signing_key'),
            defaultTtlSeconds: (int) config('embedlayer.default_ttl_seconds', 300),
        ));

        $this->app->singleton(ResolveModel::class);
        $this->app->singleton(ValidateQueryPermissions::class);
        $this->app->singleton(ApplyAccessPolicies::class);
        $this->app->singleton(CheckCache::class);
        $this->app->singleton(ExecuteQuery::class);
        $this->app->singleton(NormalizeResult::class);
        $this->app->singleton(StoreResultCache::class);
        $this->app->singleton(RecordQueryRun::class);
        $this->app->singleton(QueryExecutionPipeline::class);
    }
}
