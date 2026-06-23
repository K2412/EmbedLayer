<?php

declare(strict_types=1);

namespace App\Analytics\Actions\DataSources;

use App\Analytics\DataSources\DTOs\ConnectionTestResult;
use App\Analytics\DataSources\Support\ConnectionErrorRedactor;
use App\Analytics\DataSources\Support\DataSourceConnectorRegistry;
use App\Models\DataSource;
use Illuminate\Support\Facades\Date;
use Throwable;

/**
 * Runs the driver-specific testConnection() and stamps last_tested_at.
 * Errors are redacted before being surfaced (Plan §17.4).
 */
final readonly class TestDataSourceConnection
{
    public function __construct(private DataSourceConnectorRegistry $registry) {}

    public function handle(DataSource $dataSource): ConnectionTestResult
    {
        try {
            $result = $this->registry->for($dataSource)->testConnection();
        } catch (Throwable $e) {
            $result = ConnectionTestResult::failed(ConnectionErrorRedactor::redact($e->getMessage()));
        }

        $dataSource->forceFill(['last_tested_at' => Date::now()])->save();

        return $result;
    }
}
