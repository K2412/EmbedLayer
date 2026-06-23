<?php

declare(strict_types=1);

namespace App\Analytics\DataSources\Contracts;

use App\Analytics\Compiler\CompiledQuery;
use App\Analytics\DataSources\DTOs\ConnectionTestResult;
use App\Analytics\DataSources\DTOs\DataSourceCapabilities;
use App\Analytics\DataSources\DTOs\SchemaCatalog;
use App\Analytics\Semantic\DTOs\SemanticResult;

interface DataSourceConnector
{
    public function testConnection(): ConnectionTestResult;

    public function introspect(): SchemaCatalog;

    public function execute(CompiledQuery $query): SemanticResult;

    public function capabilities(): DataSourceCapabilities;
}
