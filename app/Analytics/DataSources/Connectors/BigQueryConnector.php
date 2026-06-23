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
use Google\Cloud\BigQuery\BigQueryClient;
use RuntimeException;

/**
 * BigQuery connector. The live path uses Google's PHP SDK; testConnection()
 * reports an actionable message if the SDK isn't installed yet so the rest
 * of the platform still boots without it.
 */
final class BigQueryConnector implements DataSourceConnector
{
    public const REQUIRED_PACKAGE = 'google/cloud-bigquery';

    public function testConnection(): ConnectionTestResult
    {
        if (! $this->sdkAvailable()) {
            return ConnectionTestResult::failed(
                'BigQuery SDK not installed; run `composer require '.self::REQUIRED_PACKAGE.'`.',
            );
        }

        throw new RuntimeException('BigQuery testConnection live path lands with the SDK install.');
    }

    public function introspect(): SchemaCatalog
    {
        $this->requireSdk();

        throw new RuntimeException('BigQueryConnector::introspect lands with the SDK install.');
    }

    public function execute(CompiledQuery $query): SemanticResult
    {
        $this->requireSdk();

        throw new RuntimeException('BigQueryConnector::execute lands with the SDK install.');
    }

    public function capabilities(): DataSourceCapabilities
    {
        return new DataSourceCapabilities(
            supportsSql: true,
            supportsJoins: true,
            supportsDateTrunc: true,
            supportedTimeGrains: ['day', 'week', 'month', 'quarter', 'year'],
            dialect: 'bigquery',
        );
    }

    private function sdkAvailable(): bool
    {
        return class_exists(BigQueryClient::class);
    }

    private function requireSdk(): void
    {
        if (! $this->sdkAvailable()) {
            throw new DataSourceConnectionException(
                'BigQuery SDK not installed; run `composer require '.self::REQUIRED_PACKAGE.'`.',
            );
        }
    }
}
