<?php

declare(strict_types=1);

namespace App\Analytics\Actions\DataSources;

use App\Analytics\DataSources\DTOs\ConnectionTestResult;
use App\Models\DataSource;
use RuntimeException;

final readonly class TestDataSourceConnection
{
    public function handle(DataSource $dataSource): ConnectionTestResult
    {
        throw new RuntimeException('TestDataSourceConnection is implemented by a later milestone (M2).');
    }
}
