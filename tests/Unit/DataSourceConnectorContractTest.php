<?php

declare(strict_types=1);

use App\Analytics\Compiler\CompiledQuery;
use App\Analytics\DataSources\Contracts\DataSourceConnector;
use App\Analytics\DataSources\DTOs\ColumnCatalog;
use App\Analytics\DataSources\DTOs\ConnectionTestResult;
use App\Analytics\DataSources\DTOs\DataSourceCapabilities;
use App\Analytics\DataSources\DTOs\SchemaCatalog;
use App\Analytics\DataSources\DTOs\TableCatalog;
use App\Analytics\Semantic\DTOs\SemanticResult;

it('builds ConnectionTestResult via success and failed factories', function () {
    $ok = ConnectionTestResult::success();
    $bad = ConnectionTestResult::failed('boom');

    expect($ok->success)->toBeTrue()
        ->and($ok->errorMessage)->toBeNull()
        ->and($bad->success)->toBeFalse()
        ->and($bad->errorMessage)->toBe('boom');
});

it('serializes DataSourceCapabilities to snake_case keys', function () {
    $caps = new DataSourceCapabilities(
        supportsSql: true,
        supportsJoins: true,
        supportsDateTrunc: true,
        supportedTimeGrains: ['day', 'month'],
        dialect: 'postgres',
    );

    expect(json_decode(json_encode($caps), associative: true))->toBe([
        'supports_sql' => true,
        'supports_joins' => true,
        'supports_date_trunc' => true,
        'supported_time_grains' => ['day', 'month'],
        'dialect' => 'postgres',
    ]);
});

it('composes SchemaCatalog from Table and Column catalogs', function () {
    $catalog = new SchemaCatalog([
        new TableCatalog(
            schema: 'public',
            name: 'orders',
            columns: [
                new ColumnCatalog(name: 'id', dataType: 'bigint', nullable: false),
                new ColumnCatalog(name: 'status', dataType: 'text'),
            ],
        ),
    ]);

    $decoded = json_decode(json_encode($catalog), associative: true);

    expect($decoded['tables'])->toHaveCount(1)
        ->and($decoded['tables'][0]['name'])->toBe('orders')
        ->and($decoded['tables'][0]['columns'])->toHaveCount(2)
        ->and($decoded['tables'][0]['columns'][0])->toBe([
            'name' => 'id',
            'data_type' => 'bigint',
            'nullable' => false,
        ]);
});

it('serializes a CompiledQuery', function () {
    $cq = new CompiledQuery(
        sql: 'SELECT 1',
        bindings: [],
        dialect: 'postgres',
        metadata: ['source' => 'unit'],
    );

    expect(json_decode(json_encode($cq), associative: true))->toBe([
        'sql' => 'SELECT 1',
        'bindings' => [],
        'dialect' => 'postgres',
        'metadata' => ['source' => 'unit'],
    ]);
});

it('can be implemented as an anonymous class', function () {
    $connector = new class implements DataSourceConnector
    {
        public function testConnection(): ConnectionTestResult
        {
            return ConnectionTestResult::success();
        }

        public function introspect(): SchemaCatalog
        {
            return new SchemaCatalog([]);
        }

        public function execute(CompiledQuery $query): SemanticResult
        {
            return new SemanticResult(columns: [], rows: [], metadata: ['sql' => $query->sql]);
        }

        public function capabilities(): DataSourceCapabilities
        {
            return new DataSourceCapabilities(
                supportsSql: true,
                supportsJoins: false,
                supportsDateTrunc: false,
                supportedTimeGrains: [],
                dialect: 'test',
            );
        }
    };

    $result = $connector->execute(new CompiledQuery(sql: 'SELECT 42', bindings: [], dialect: 'test'));

    expect($connector)->toBeInstanceOf(DataSourceConnector::class)
        ->and($connector->testConnection()->success)->toBeTrue()
        ->and($connector->capabilities()->dialect)->toBe('test')
        ->and($result->metadata['sql'])->toBe('SELECT 42');
});
