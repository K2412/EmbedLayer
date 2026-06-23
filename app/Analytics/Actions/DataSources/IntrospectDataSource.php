<?php

declare(strict_types=1);

namespace App\Analytics\Actions\DataSources;

use App\Analytics\DataSources\DTOs\SchemaCatalog;
use App\Analytics\DataSources\Support\DataSourceConnectorRegistry;
use App\Models\DataSource;
use Illuminate\Support\Facades\Date;

/**
 * Calls the connector's introspect() and persists the catalog +
 * capabilities into the DataSource record so the rest of the system can
 * read them without re-querying the warehouse.
 */
final readonly class IntrospectDataSource
{
    public function __construct(private DataSourceConnectorRegistry $registry) {}

    public function handle(DataSource $dataSource): SchemaCatalog
    {
        $connector = $this->registry->for($dataSource);

        $catalog = $connector->introspect();
        $capabilities = $connector->capabilities();

        $dataSource->forceFill([
            'last_introspected_schema' => $catalog->jsonSerialize(),
            'capabilities' => $capabilities->jsonSerialize(),
            'last_introspected_at' => Date::now(),
        ])->save();

        return $catalog;
    }
}
