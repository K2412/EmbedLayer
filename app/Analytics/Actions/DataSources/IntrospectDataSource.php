<?php

declare(strict_types=1);

namespace App\Analytics\Actions\DataSources;

use App\Analytics\DataSources\DTOs\SchemaCatalog;
use App\Models\DataSource;
use RuntimeException;

final readonly class IntrospectDataSource
{
    public function handle(DataSource $dataSource): SchemaCatalog
    {
        throw new RuntimeException('IntrospectDataSource is implemented by a later milestone (M2).');
    }
}
