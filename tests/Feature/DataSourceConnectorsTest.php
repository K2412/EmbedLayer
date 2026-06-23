<?php

declare(strict_types=1);

use App\Analytics\Actions\DataSources\IntrospectDataSource;
use App\Analytics\Actions\DataSources\TestDataSourceConnection;
use App\Analytics\DataSources\Connectors\BigQueryConnector;
use App\Analytics\DataSources\Connectors\MySqlConnector;
use App\Analytics\DataSources\Connectors\PostgresConnector;
use App\Analytics\DataSources\Connectors\SnowflakeConnector;
use App\Analytics\DataSources\Support\AnalyticsConnectionFactory;
use App\Analytics\DataSources\Support\ConnectionErrorRedactor;
use App\Analytics\DataSources\Support\DataSourceConnectorRegistry;
use App\Analytics\Support\Exceptions\DataSourceConnectionException;
use App\Models\DataSource;
use App\Models\Organization;
use Illuminate\Database\Connection;
use Illuminate\Encryption\Encrypter;

beforeEach(function () {
    config()->set('embedlayer.credential_encryption_key', Encrypter::generateKey('aes-256-gcm'));
    config()->set('embedlayer.previous_credential_encryption_keys', []);
});

afterEach(function () {
    AnalyticsConnectionFactory::fakeUsing(null);
});

it('selects the right connector for each driver', function () {
    $org = Organization::factory()->create();
    $registry = app(DataSourceConnectorRegistry::class);

    $cases = [
        'postgres' => PostgresConnector::class,
        'mysql' => MySqlConnector::class,
        'bigquery' => BigQueryConnector::class,
        'snowflake' => SnowflakeConnector::class,
    ];

    foreach ($cases as $driver => $expected) {
        $ds = DataSource::factory()->create(['organization_id' => $org->id, 'driver' => $driver]);
        expect($registry->for($ds))->toBeInstanceOf($expected);
    }
});

it('throws when no connector matches the driver', function () {
    $ds = DataSource::factory()->create(['driver' => 'clickhouse']);

    expect(fn () => app(DataSourceConnectorRegistry::class)->for($ds))
        ->toThrow(DataSourceConnectionException::class);
});

it('TestDataSourceConnection reports success and stamps last_tested_at', function () {
    AnalyticsConnectionFactory::fakeUsing(function (DataSource $ds, string $driver, array $config): Connection {
        $fake = Mockery::mock(Connection::class);
        $fake->shouldReceive('select')->with('SELECT 1')->andReturn([0 => (object) ['?column?' => 1]]);

        return $fake;
    });

    $ds = DataSource::factory()->create(['driver' => 'postgres']);

    $result = app(TestDataSourceConnection::class)->handle($ds);

    expect($result->success)->toBeTrue()
        ->and($ds->fresh()->last_tested_at)->not->toBeNull();
});

it('TestDataSourceConnection redacts secrets in failures', function () {
    AnalyticsConnectionFactory::fakeUsing(function (DataSource $ds, string $driver, array $config): Connection {
        $fake = Mockery::mock(Connection::class);
        $fake->shouldReceive('select')
            ->andThrow(new RuntimeException('connect failed: password=hunter2 host=db.example.com'));

        return $fake;
    });

    $ds = DataSource::factory()->create(['driver' => 'postgres']);

    $result = app(TestDataSourceConnection::class)->handle($ds);

    expect($result->success)->toBeFalse()
        ->and($result->errorMessage)->not->toContain('hunter2')
        ->and($result->errorMessage)->toContain('[REDACTED]');
});

it('IntrospectDataSource persists the catalog + capabilities', function () {
    AnalyticsConnectionFactory::fakeUsing(function (DataSource $ds, string $driver, array $config): Connection {
        $fake = Mockery::mock(Connection::class);
        $fake->shouldReceive('select')->andReturn([
            (object) ['table_schema' => 'public', 'table_name' => 'orders', 'column_name' => 'id', 'data_type' => 'bigint', 'is_nullable' => 'NO'],
            (object) ['table_schema' => 'public', 'table_name' => 'orders', 'column_name' => 'total', 'data_type' => 'numeric', 'is_nullable' => 'YES'],
        ]);

        return $fake;
    });

    $ds = DataSource::factory()->create(['driver' => 'postgres']);

    $catalog = app(IntrospectDataSource::class)->handle($ds);

    $fresh = $ds->fresh();

    expect($catalog->tables)->toHaveCount(1)
        ->and($catalog->tables[0]->columns)->toHaveCount(2)
        ->and($fresh->last_introspected_at)->not->toBeNull()
        ->and($fresh->capabilities)->toMatchArray(['dialect' => 'postgres', 'supports_sql' => true])
        ->and($fresh->last_introspected_schema['tables'][0]['name'])->toBe('orders');
});

it('BigQueryConnector::testConnection reports the missing SDK without crashing', function () {
    $result = (new BigQueryConnector)->testConnection();

    expect($result->success)->toBeFalse()
        ->and($result->errorMessage)->toContain('google/cloud-bigquery');
});

it('SnowflakeConnector::testConnection reports the missing driver without crashing', function () {
    $result = (new SnowflakeConnector)->testConnection();

    expect($result->success)->toBeFalse()
        ->and($result->errorMessage)->toContain('Snowflake');
});

it('ConnectionErrorRedactor scrubs assorted secret-keyed values', function () {
    expect(ConnectionErrorRedactor::redact('password=hunter2 host=db'))->toBe('password=[REDACTED] host=db')
        ->and(ConnectionErrorRedactor::redact('token: abc'))->toBe('token: [REDACTED]')
        ->and(ConnectionErrorRedactor::redact('Bearer authorization=secret123'))->toContain('[REDACTED]');
});
