<?php

declare(strict_types=1);

namespace App\Analytics\DataSources\Support;

use App\Analytics\DataSources\Connectors\BigQueryConnector;
use App\Analytics\DataSources\Connectors\MySqlConnector;
use App\Analytics\DataSources\Connectors\PostgresConnector;
use App\Analytics\DataSources\Connectors\SnowflakeConnector;
use App\Analytics\DataSources\Contracts\DataSourceConnector;
use App\Analytics\Support\Exceptions\DataSourceConnectionException;
use App\Models\DataSource;

/**
 * Maps a DataSource's driver string to its connector implementation.
 * Plan §9: every driver speaks the same DataSourceConnector contract.
 */
final class DataSourceConnectorRegistry
{
    public function __construct(private readonly AnalyticsConnectionFactory $connections) {}

    public function for(DataSource $dataSource): DataSourceConnector
    {
        return match ($dataSource->driver) {
            'postgres', 'pgsql' => new PostgresConnector($dataSource, $this->connections),
            'mysql' => new MySqlConnector($dataSource, $this->connections),
            'bigquery' => new BigQueryConnector,
            'snowflake' => new SnowflakeConnector,
            default => throw new DataSourceConnectionException(
                "No connector registered for driver `{$dataSource->driver}`.",
            ),
        };
    }
}
