<?php

declare(strict_types=1);

use App\Analytics\Compiler\AccessPolicyCompiler;
use App\Analytics\Compiler\Dialects\BigQueryDialect;
use App\Analytics\Compiler\Dialects\MySqlDialect;
use App\Analytics\Compiler\Dialects\PostgresDialect;
use App\Analytics\Compiler\Dialects\SnowflakeDialect;
use App\Analytics\Compiler\Dialects\SqlDialectResolver;
use App\Analytics\Compiler\FieldValidator;
use App\Analytics\Compiler\InternalQueryCompiler;
use App\Analytics\Semantic\DTOs\Filter;
use App\Analytics\Semantic\DTOs\ProviderContext;
use App\Analytics\Semantic\DTOs\SemanticQuery;
use App\Analytics\Semantic\DTOs\TimeDimension;
use App\Analytics\Support\Exceptions\SemanticModelValidationException;
use App\Analytics\Support\Exceptions\UnsupportedAggregateException;
use App\Models\AccessPolicy;
use App\Models\AnalyticsJoin;
use App\Models\DataSource;
use App\Models\Dimension;
use App\Models\Measure;
use App\Models\Organization;
use App\Models\SemanticModel;
use App\Models\SemanticProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $modelAttrs
 */
function compilerOrdersModel(string $driver = 'postgres', array $modelAttrs = []): SemanticModel
{
    $organization = Organization::factory()->create();
    $dataSource = DataSource::factory()->create([
        'organization_id' => $organization->id,
        'driver' => $driver,
    ]);
    $provider = SemanticProvider::factory()->create([
        'organization_id' => $organization->id,
        'data_source_id' => $dataSource->id,
        'type' => 'internal',
    ]);

    return SemanticModel::factory()->create(array_merge([
        'organization_id' => $organization->id,
        'semantic_provider_id' => $provider->id,
        'base_table' => 'public.orders',
        'base_table_alias' => 'o',
        'name' => 'orders',
    ], $modelAttrs));
}

function compilerInstance(): InternalQueryCompiler
{
    return new InternalQueryCompiler(
        dialects: new SqlDialectResolver,
        fieldValidator: new FieldValidator,
        accessPolicyCompiler: new AccessPolicyCompiler,
    );
}

function compilerContext(): ProviderContext
{
    return new ProviderContext(
        organizationId: 'org_1',
        projectId: 'proj_1',
        externalAccountId: 'acct_42',
        embedId: 'embed_x',
        claims: ['external_account_id' => 'acct_42', 'tenant_id' => 'tenant_7'],
    );
}

function seedRevenueAndCountry(SemanticModel $model): void
{
    Measure::factory()->create([
        'semantic_model_id' => $model->id,
        'name' => 'revenue',
        'type' => 'sum',
        'column' => 'total_amount',
        'expression' => null,
        'is_public' => true,
    ]);
    Dimension::factory()->create([
        'semantic_model_id' => $model->id,
        'name' => 'country',
        'type' => 'string',
        'column' => 'shipping_country',
        'table_alias' => null,
        'is_public' => true,
        'allowed_time_grains' => null,
    ]);
}

it('compiles a basic SELECT with one measure and one dimension on Postgres', function () {
    $model = compilerOrdersModel('postgres');
    seedRevenueAndCountry($model);

    $compiled = compilerInstance()->compile(
        $model->fresh(['measures', 'dimensions', 'joins', 'semanticProvider.dataSource']),
        new SemanticQuery(model: 'orders', measures: ['revenue'], dimensions: ['country']),
        compilerContext(),
    );

    expect($compiled->sql)->toBe(
        'SELECT "o"."shipping_country" AS "country", SUM("o"."total_amount") AS "revenue"'
        .' FROM public.orders AS "o"'
        .' GROUP BY "o"."shipping_country"'
    )->and($compiled->bindings)->toBe([])->and($compiled->dialect)->toBe('postgres');
});

it('compiles a time dimension via dialect dateTrunc on Postgres', function () {
    $model = compilerOrdersModel('postgres');
    Measure::factory()->create([
        'semantic_model_id' => $model->id,
        'name' => 'orders_count',
        'type' => 'count',
        'column' => null,
        'expression' => null,
        'is_public' => true,
    ]);
    Dimension::factory()->create([
        'semantic_model_id' => $model->id,
        'name' => 'created_at',
        'type' => 'time',
        'column' => 'created_at',
        'table_alias' => null,
        'is_public' => true,
        'allowed_time_grains' => ['day', 'month'],
    ]);

    $compiled = compilerInstance()->compile(
        $model->fresh(['measures', 'dimensions', 'joins', 'semanticProvider.dataSource']),
        new SemanticQuery(
            model: 'orders',
            measures: ['orders_count'],
            dimensions: [],
            timeDimension: new TimeDimension('created_at', 'month'),
        ),
        compilerContext(),
    );

    expect($compiled->sql)->toBe(
        'SELECT DATE_TRUNC(\'month\', "o"."created_at") AS "created_at", COUNT(*) AS "orders_count"'
        .' FROM public.orders AS "o"'
        .' GROUP BY DATE_TRUNC(\'month\', "o"."created_at")'
    );
});

it('uses DATE_FORMAT for month grain on MySQL', function () {
    $model = compilerOrdersModel('mysql');
    Measure::factory()->create([
        'semantic_model_id' => $model->id,
        'name' => 'orders_count',
        'type' => 'count',
        'column' => null,
        'expression' => null,
        'is_public' => true,
    ]);
    Dimension::factory()->create([
        'semantic_model_id' => $model->id,
        'name' => 'created_at',
        'type' => 'time',
        'column' => 'created_at',
        'table_alias' => null,
        'is_public' => true,
        'allowed_time_grains' => ['day', 'month'],
    ]);

    $compiled = compilerInstance()->compile(
        $model->fresh(['measures', 'dimensions', 'joins', 'semanticProvider.dataSource']),
        new SemanticQuery(
            model: 'orders',
            measures: ['orders_count'],
            dimensions: [],
            timeDimension: new TimeDimension('created_at', 'month'),
        ),
        compilerContext(),
    );

    expect($compiled->sql)->toBe(
        'SELECT DATE_FORMAT(`o`.`created_at`, \'%Y-%m-01\') AS `created_at`, COUNT(*) AS `orders_count`'
        .' FROM public.orders AS `o`'
        .' GROUP BY DATE_FORMAT(`o`.`created_at`, \'%Y-%m-01\')'
    );
});

it('compiles BigQuery DATE_TRUNC and backtick identifiers', function () {
    $model = compilerOrdersModel('bigquery');
    seedRevenueAndCountry($model);
    Dimension::factory()->create([
        'semantic_model_id' => $model->id,
        'name' => 'created_at',
        'type' => 'time',
        'column' => 'created_at',
        'table_alias' => null,
        'is_public' => true,
        'allowed_time_grains' => ['day', 'month'],
    ]);

    $compiled = compilerInstance()->compile(
        $model->fresh(['measures', 'dimensions', 'joins', 'semanticProvider.dataSource']),
        new SemanticQuery(
            model: 'orders',
            measures: ['revenue'],
            dimensions: [],
            timeDimension: new TimeDimension('created_at', 'day'),
        ),
        compilerContext(),
    );

    expect($compiled->sql)->toBe(
        'SELECT DATE_TRUNC(DATE(`o`.`created_at`), DAY) AS `created_at`, SUM(`o`.`total_amount`) AS `revenue`'
        .' FROM public.orders AS `o`'
        .' GROUP BY DATE_TRUNC(DATE(`o`.`created_at`), DAY)'
    )->and($compiled->dialect)->toBe('bigquery');
});

it('compiles Snowflake with double-quoted identifiers and DATE_TRUNC', function () {
    $model = compilerOrdersModel('snowflake');
    seedRevenueAndCountry($model);
    Dimension::factory()->create([
        'semantic_model_id' => $model->id,
        'name' => 'created_at',
        'type' => 'time',
        'column' => 'created_at',
        'table_alias' => null,
        'is_public' => true,
        'allowed_time_grains' => ['day', 'month'],
    ]);

    $compiled = compilerInstance()->compile(
        $model->fresh(['measures', 'dimensions', 'joins', 'semanticProvider.dataSource']),
        new SemanticQuery(
            model: 'orders',
            measures: ['revenue'],
            dimensions: [],
            timeDimension: new TimeDimension('created_at', 'day'),
        ),
        compilerContext(),
    );

    expect($compiled->sql)->toBe(
        'SELECT DATE_TRUNC(\'day\', "o"."created_at") AS "created_at", SUM("o"."total_amount") AS "revenue"'
        .' FROM public.orders AS "o"'
        .' GROUP BY DATE_TRUNC(\'day\', "o"."created_at")'
    )->and($compiled->dialect)->toBe('snowflake');
});

it('compiles a ratio measure into (SUM)/NULLIF((SUM),0)', function () {
    $model = compilerOrdersModel('postgres');
    Measure::factory()->create([
        'semantic_model_id' => $model->id,
        'name' => 'revenue',
        'type' => 'sum',
        'column' => 'total_amount',
        'expression' => null,
        'is_public' => true,
    ]);
    Measure::factory()->create([
        'semantic_model_id' => $model->id,
        'name' => 'orders_count',
        'type' => 'count',
        'column' => null,
        'expression' => null,
        'is_public' => true,
    ]);
    Measure::factory()->create([
        'semantic_model_id' => $model->id,
        'name' => 'aov',
        'type' => 'ratio',
        'column' => null,
        'expression' => ['numerator' => 'revenue', 'denominator' => 'orders_count'],
        'is_public' => true,
    ]);

    $compiled = compilerInstance()->compile(
        $model->fresh(['measures', 'dimensions', 'joins', 'semanticProvider.dataSource']),
        new SemanticQuery(model: 'orders', measures: ['aov']),
        compilerContext(),
    );

    expect($compiled->sql)->toBe(
        'SELECT (SUM("o"."total_amount")) / NULLIF((COUNT(*)), 0) AS "aov"'
        .' FROM public.orders AS "o"'
    );
});

it('resolves a filter value_from_context against ProviderContext claims', function () {
    $model = compilerOrdersModel('postgres');
    seedRevenueAndCountry($model);

    $compiled = compilerInstance()->compile(
        $model->fresh(['measures', 'dimensions', 'joins', 'semanticProvider.dataSource']),
        new SemanticQuery(
            model: 'orders',
            measures: ['revenue'],
            dimensions: ['country'],
            filters: [
                new Filter(field: 'country', operator: '=', valueFromContext: 'tenant_id'),
            ],
        ),
        compilerContext(),
    );

    expect($compiled->sql)->toBe(
        'SELECT "o"."shipping_country" AS "country", SUM("o"."total_amount") AS "revenue"'
        .' FROM public.orders AS "o"'
        .' WHERE "o"."shipping_country" = ?'
        .' GROUP BY "o"."shipping_country"'
    )->and($compiled->bindings)->toBe(['tenant_7']);
});

it('injects required access policies bound from ProviderContext claims', function () {
    $model = compilerOrdersModel('postgres');
    seedRevenueAndCountry($model);

    AccessPolicy::query()->create([
        'semantic_model_id' => $model->id,
        'name' => 'tenant_isolation',
        'rules' => [
            ['field' => 'account_id', 'operator' => '=', 'value_from_context' => 'external_account_id'],
        ],
        'is_required' => true,
    ]);

    $compiled = compilerInstance()->compile(
        $model->fresh(['measures', 'dimensions', 'joins', 'accessPolicies', 'semanticProvider.dataSource']),
        new SemanticQuery(model: 'orders', measures: ['revenue'], dimensions: ['country']),
        compilerContext(),
    );

    expect($compiled->sql)->toContain('WHERE ("o"."account_id" = ?)')
        ->and($compiled->bindings)->toBe(['acct_42']);
});

it('rejects unknown measures via FieldValidator', function () {
    $model = compilerOrdersModel('postgres');
    seedRevenueAndCountry($model);

    expect(fn () => compilerInstance()->compile(
        $model->fresh(['measures', 'dimensions', 'joins', 'semanticProvider.dataSource']),
        new SemanticQuery(model: 'orders', measures: ['mystery']),
        compilerContext(),
    ))->toThrow(SemanticModelValidationException::class, 'Unknown measure');
});

it('throws UnsupportedAggregateException from the dialect on unknown aggregate', function () {
    expect(fn () => (new PostgresDialect)->aggregate('median', '"x"."y"'))
        ->toThrow(UnsupportedAggregateException::class);
});

it('renders LIMIT and OFFSET via dialect at the tail of the SQL', function () {
    $model = compilerOrdersModel('postgres');
    seedRevenueAndCountry($model);

    $compiled = compilerInstance()->compile(
        $model->fresh(['measures', 'dimensions', 'joins', 'semanticProvider.dataSource']),
        new SemanticQuery(
            model: 'orders',
            measures: ['revenue'],
            dimensions: ['country'],
            limit: 100,
            offset: 25,
        ),
        compilerContext(),
    );

    expect($compiled->sql)->toEndWith(' LIMIT 100 OFFSET 25');
});

it('emits a LEFT JOIN when a dimension references a joined table_alias', function () {
    $model = compilerOrdersModel('postgres');
    Measure::factory()->create([
        'semantic_model_id' => $model->id,
        'name' => 'revenue',
        'type' => 'sum',
        'column' => 'total_amount',
        'expression' => null,
        'is_public' => true,
    ]);
    Dimension::factory()->create([
        'semantic_model_id' => $model->id,
        'name' => 'customer_name',
        'type' => 'string',
        'column' => 'name',
        'table_alias' => 'c',
        'is_public' => true,
        'allowed_time_grains' => null,
    ]);
    AnalyticsJoin::factory()->create([
        'semantic_model_id' => $model->id,
        'name' => 'customers',
        'left_table_alias' => 'o',
        'left_column' => 'customer_id',
        'right_table' => 'public.customers',
        'right_table_alias' => 'c',
        'right_column' => 'id',
        'type' => 'left',
        'relationship' => 'many_to_one',
    ]);

    $compiled = compilerInstance()->compile(
        $model->fresh(['measures', 'dimensions', 'joins', 'semanticProvider.dataSource']),
        new SemanticQuery(model: 'orders', measures: ['revenue'], dimensions: ['customer_name']),
        compilerContext(),
    );

    expect($compiled->sql)->toBe(
        'SELECT "c"."name" AS "customer_name", SUM("o"."total_amount") AS "revenue"'
        .' FROM public.orders AS "o"'
        .' LEFT JOIN public.customers AS "c" ON "o"."customer_id" = "c"."id"'
        .' GROUP BY "c"."name"'
    );
});

it('rejects time grain not in allowed_time_grains', function () {
    $model = compilerOrdersModel('postgres');
    Measure::factory()->create([
        'semantic_model_id' => $model->id,
        'name' => 'orders_count',
        'type' => 'count',
        'column' => null,
        'expression' => null,
        'is_public' => true,
    ]);
    Dimension::factory()->create([
        'semantic_model_id' => $model->id,
        'name' => 'created_at',
        'type' => 'time',
        'column' => 'created_at',
        'table_alias' => null,
        'is_public' => true,
        'allowed_time_grains' => ['day'],
    ]);

    expect(fn () => compilerInstance()->compile(
        $model->fresh(['measures', 'dimensions', 'joins', 'semanticProvider.dataSource']),
        new SemanticQuery(
            model: 'orders',
            measures: ['orders_count'],
            timeDimension: new TimeDimension('created_at', 'month'),
        ),
        compilerContext(),
    ))->toThrow(SemanticModelValidationException::class, 'Time grain');
});

it('demonstrates BigQuery and MySQL dialect identifier escaping', function () {
    expect((new BigQueryDialect)->quoteIdentifier('orders'))->toBe('`orders`')
        ->and((new MySqlDialect)->quoteIdentifier('orders'))->toBe('`orders`')
        ->and((new SnowflakeDialect)->quoteIdentifier('orders'))->toBe('"orders"')
        ->and((new PostgresDialect)->quoteIdentifier('he"llo'))->toBe('"he""llo"');
});
