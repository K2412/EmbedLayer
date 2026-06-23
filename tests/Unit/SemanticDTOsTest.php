<?php

declare(strict_types=1);

use App\Analytics\Semantic\DTOs\Filter;
use App\Analytics\Semantic\DTOs\ProviderContext;
use App\Analytics\Semantic\DTOs\SemanticQuery;
use App\Analytics\Semantic\DTOs\SemanticResult;
use App\Analytics\Semantic\DTOs\TimeDimension;

it('serializes a TimeDimension', function () {
    $td = new TimeDimension(name: 'created_at', grain: 'month');

    expect(json_encode($td))->toBe('{"name":"created_at","grain":"month"}');
});

it('serializes a Filter with a literal value', function () {
    $filter = new Filter(field: 'status', operator: '=', value: 'paid');

    expect(json_encode($filter))->toBe('{"field":"status","operator":"=","value":"paid"}');
});

it('serializes a Filter with value_from_context and omits the literal value', function () {
    $filter = new Filter(field: 'account_id', operator: '=', valueFromContext: 'external_account_id');

    expect(json_encode($filter))->toBe('{"field":"account_id","operator":"=","value_from_context":"external_account_id"}');
});

it('builds a SemanticQuery and serializes nested DTOs', function () {
    $query = new SemanticQuery(
        model: 'orders',
        measures: ['revenue'],
        dimensions: ['country'],
        timeDimension: new TimeDimension(name: 'created_at', grain: 'month'),
        filters: [new Filter(field: 'status', operator: '=', value: 'paid')],
        limit: 500,
    );

    $decoded = json_decode(json_encode($query), associative: true);

    expect($decoded)
        ->toMatchArray([
            'model' => 'orders',
            'measures' => ['revenue'],
            'dimensions' => ['country'],
            'limit' => 500,
            'offset' => null,
        ])
        ->and($decoded['time_dimension'])->toBe(['name' => 'created_at', 'grain' => 'month'])
        ->and($decoded['filters'])->toBe([['field' => 'status', 'operator' => '=', 'value' => 'paid']]);
});

it('builds a SemanticResult with columns, rows and metadata', function () {
    $result = new SemanticResult(
        columns: [['key' => 'country', 'label' => 'Country', 'type' => 'string']],
        rows: [['country' => 'CA']],
        metadata: ['cache_hit' => false],
    );

    expect($result->columns)->toHaveCount(1)
        ->and($result->rows)->toBe([['country' => 'CA']])
        ->and($result->metadata['cache_hit'])->toBeFalse();
});

it('builds a ProviderContext and serializes embed-aware fields', function () {
    $ctx = new ProviderContext(
        organizationId: 'org_1',
        projectId: 'proj_1',
        externalAccountId: 'acct_9',
        embedId: 'embed_x',
        claims: ['foo' => 'bar'],
    );

    expect(json_decode(json_encode($ctx), associative: true))->toBe([
        'organization_id' => 'org_1',
        'project_id' => 'proj_1',
        'external_account_id' => 'acct_9',
        'embed_id' => 'embed_x',
        'claims' => ['foo' => 'bar'],
    ]);
});

it('enforces immutability via final readonly', function () {
    $td = new TimeDimension(name: 'created_at', grain: 'day');

    expect(fn () => $td->grain = 'month')->toThrow(Error::class);
});
