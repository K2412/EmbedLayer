<?php

declare(strict_types=1);

namespace App\Analytics\DataSources\Support;

use App\Analytics\Security\CredentialVault;
use App\Models\DataSource;
use Closure;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Builds dynamic Laravel database connections for analytics data sources.
 * Connection configs are registered under `database.connections.analytics_<id>`
 * so multiple data sources can coexist with the host app's primary connection.
 *
 * Test seams: a Closure resolver can be substituted to inject a fake Connection.
 */
final class AnalyticsConnectionFactory
{
    /** @var Closure(DataSource, string, array<string, mixed>): Connection|null */
    private static ?Closure $testResolver = null;

    public function __construct(private readonly CredentialVault $vault) {}

    /**
     * @param  array<string, mixed>  $extraConfig  driver-specific overrides
     */
    public function makeForDriver(DataSource $dataSource, string $laravelDriver, array $extraConfig = []): Connection
    {
        $config = $this->vault->decryptDataSourceConfig($dataSource);

        if (self::$testResolver !== null) {
            return (self::$testResolver)($dataSource, $laravelDriver, $config + $extraConfig);
        }

        $connectionName = $this->connectionName($dataSource);

        Config::set("database.connections.{$connectionName}", [
            'driver' => $laravelDriver,
            'host' => $config['host'] ?? null,
            'port' => $config['port'] ?? null,
            'database' => $config['database'] ?? null,
            'username' => $config['username'] ?? null,
            'password' => $config['password'] ?? null,
        ] + $extraConfig);

        DB::purge($connectionName);

        return DB::connection($connectionName);
    }

    public function connectionName(DataSource $dataSource): string
    {
        return 'analytics_'.$dataSource->id;
    }

    /**
     * Install a test-only resolver. Pass null to clear.
     *
     * @param  Closure(DataSource, string, array<string, mixed>): Connection|null  $resolver
     */
    public static function fakeUsing(?Closure $resolver): void
    {
        self::$testResolver = $resolver;
    }
}
