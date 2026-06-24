<?php

declare(strict_types=1);

namespace App\Analytics\Compiler\Dialects;

use App\Models\DataSource;
use RuntimeException;

final class SqlDialectResolver
{
    public function forDataSource(DataSource $dataSource): SqlDialect
    {
        return $this->forDriver($dataSource->driver);
    }

    public function forDriver(string $driver): SqlDialect
    {
        return match ($driver) {
            'postgres', 'pgsql' => new PostgresDialect,
            'mysql' => new MySqlDialect,
            'bigquery' => new BigQueryDialect,
            'snowflake' => new SnowflakeDialect,
            default => throw new RuntimeException("No SQL dialect registered for driver `{$driver}`."),
        };
    }
}
