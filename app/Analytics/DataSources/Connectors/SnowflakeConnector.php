<?php

declare(strict_types=1);

namespace App\Analytics\DataSources\Connectors;

use App\Analytics\Compiler\CompiledQuery;
use App\Analytics\DataSources\Contracts\DataSourceConnector;
use App\Analytics\DataSources\DTOs\ConnectionTestResult;
use App\Analytics\DataSources\DTOs\DataSourceCapabilities;
use App\Analytics\DataSources\DTOs\SchemaCatalog;
use App\Analytics\Semantic\DTOs\SemanticResult;
use App\Analytics\Support\Exceptions\DataSourceConnectionException;
use RuntimeException;

/**
 * Snowflake connector. PHP has no first-party Snowflake driver; the live
 * path uses either `pdo_odbc` + Snowflake ODBC drivers, or a key-pair JWT
 * client against the SQL REST API. testConnection() reports the missing
 * dependency without crashing the rest of the platform.
 */
final class SnowflakeConnector implements DataSourceConnector
{
    public const REQUIRED_EXTENSION = 'pdo_odbc';

    public function testConnection(): ConnectionTestResult
    {
        if (! $this->driverAvailable()) {
            return ConnectionTestResult::failed(
                'Snowflake driver not installed; enable `'.self::REQUIRED_EXTENSION.'` and install Snowflake ODBC drivers, or implement the REST-API client.',
            );
        }

        throw new RuntimeException('Snowflake testConnection live path lands with the driver install.');
    }

    public function introspect(): SchemaCatalog
    {
        $this->requireDriver();

        throw new RuntimeException('SnowflakeConnector::introspect lands with the driver install.');
    }

    public function execute(CompiledQuery $query): SemanticResult
    {
        $this->requireDriver();

        throw new RuntimeException('SnowflakeConnector::execute lands with the driver install.');
    }

    public function capabilities(): DataSourceCapabilities
    {
        return new DataSourceCapabilities(
            supportsSql: true,
            supportsJoins: true,
            supportsDateTrunc: true,
            supportedTimeGrains: ['day', 'week', 'month', 'quarter', 'year'],
            dialect: 'snowflake',
        );
    }

    private function driverAvailable(): bool
    {
        return extension_loaded(self::REQUIRED_EXTENSION);
    }

    private function requireDriver(): void
    {
        if (! $this->driverAvailable()) {
            throw new DataSourceConnectionException(
                'Snowflake driver not installed; enable `'.self::REQUIRED_EXTENSION.'` and install Snowflake ODBC drivers.',
            );
        }
    }
}
