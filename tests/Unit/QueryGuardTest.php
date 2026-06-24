<?php

declare(strict_types=1);

use App\Analytics\DataSources\Support\QueryGuard;
use Illuminate\Database\Connection;
use Tests\TestCase;

uses(TestCase::class);

it('appends a LIMIT when the SQL has none', function () {
    config()->set('embedlayer.default_row_limit', 5000);

    expect(QueryGuard::ensureRowLimit('SELECT * FROM orders'))
        ->toBe('SELECT * FROM orders LIMIT 5000');
});

it('respects an existing LIMIT', function () {
    expect(QueryGuard::ensureRowLimit('SELECT * FROM orders LIMIT 50'))
        ->toBe('SELECT * FROM orders LIMIT 50');
});

it('strips trailing semicolons before appending LIMIT', function () {
    expect(QueryGuard::ensureRowLimit('SELECT * FROM orders;  ', 100))
        ->toBe('SELECT * FROM orders LIMIT 100');
});

it('issues SET statement_timeout for postgres', function () {
    config()->set('embedlayer.default_query_timeout_ms', 9000);

    $mock = Mockery::mock(Connection::class);
    $mock->shouldReceive('statement')->once()->with('SET statement_timeout = 9000');

    QueryGuard::applyStatementTimeout($mock, 'postgres');
});

it('issues SET SESSION max_execution_time for mysql', function () {
    $mock = Mockery::mock(Connection::class);
    $mock->shouldReceive('statement')->once()->with('SET SESSION max_execution_time = 12000');

    QueryGuard::applyStatementTimeout($mock, 'mysql', 12000);
});

it('skips timeout entirely when configured to 0', function () {
    $mock = Mockery::mock(Connection::class);
    $mock->shouldNotReceive('statement');

    QueryGuard::applyStatementTimeout($mock, 'postgres', 0);
});

it('skips timeout for unknown dialects', function () {
    $mock = Mockery::mock(Connection::class);
    $mock->shouldNotReceive('statement');

    QueryGuard::applyStatementTimeout($mock, 'snowflake');
});
