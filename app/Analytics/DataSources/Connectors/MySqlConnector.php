<?php

declare(strict_types=1);

namespace App\Analytics\DataSources\Connectors;

use App\Analytics\Compiler\CompiledQuery;
use App\Analytics\DataSources\Contracts\DataSourceConnector;
use App\Analytics\DataSources\DTOs\ConnectionTestResult;
use App\Analytics\DataSources\DTOs\DataSourceCapabilities;
use App\Analytics\DataSources\DTOs\SchemaCatalog;
use App\Analytics\DataSources\Support\AnalyticsConnectionFactory;
use App\Analytics\DataSources\Support\ConnectionErrorRedactor;
use App\Analytics\Semantic\DTOs\SemanticResult;
use App\Analytics\Support\Exceptions\QueryExecutionException;
use App\Models\DataSource;
use Illuminate\Database\Connection;
use Throwable;

final class MySqlConnector implements DataSourceConnector
{
    public function __construct(
        private readonly DataSource $dataSource,
        private readonly AnalyticsConnectionFactory $connections,
    ) {}

    public function testConnection(): ConnectionTestResult
    {
        try {
            $this->makeConnection()->select('SELECT 1');

            return ConnectionTestResult::success();
        } catch (Throwable $e) {
            return ConnectionTestResult::failed(ConnectionErrorRedactor::redact($e->getMessage()));
        }
    }

    public function introspect(): SchemaCatalog
    {
        $connection = $this->makeConnection();
        $database = $connection->getDatabaseName();

        $rows = $connection->select(<<<'SQL'
            SELECT table_schema, table_name, column_name, data_type, is_nullable
            FROM information_schema.columns
            WHERE table_schema = ?
            ORDER BY table_schema, table_name, ordinal_position
        SQL, [$database]);

        return SchemaCatalog::fromInformationSchemaRows($rows);
    }

    public function execute(CompiledQuery $query): SemanticResult
    {
        $started = microtime(true);

        try {
            $rows = $this->makeConnection()->select($query->sql, $query->bindings);
        } catch (Throwable $e) {
            throw new QueryExecutionException(ConnectionErrorRedactor::redact($e->getMessage()), previous: $e);
        }

        return SemanticResult::fromRows(
            rows: array_map(static fn ($row): array => (array) $row, $rows),
            metadata: ['query_time_ms' => (int) ((microtime(true) - $started) * 1000)],
        );
    }

    public function capabilities(): DataSourceCapabilities
    {
        return new DataSourceCapabilities(
            supportsSql: true,
            supportsJoins: true,
            supportsDateTrunc: false,
            supportedTimeGrains: ['day', 'week', 'month', 'quarter', 'year'],
            dialect: 'mysql',
        );
    }

    private function makeConnection(): Connection
    {
        return $this->connections->makeForDriver($this->dataSource, 'mysql', [
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ]);
    }
}
