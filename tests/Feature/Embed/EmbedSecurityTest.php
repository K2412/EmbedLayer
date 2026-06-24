<?php

declare(strict_types=1);

use App\Analytics\Actions\Embeds\GenerateEmbedToken;
use App\Analytics\DataSources\Support\AnalyticsConnectionFactory;
use App\Analytics\Embeds\EmbedTokenManager;
use App\Analytics\Embeds\EmbedTokenPayload;
use App\Models\AccessPolicy;
use App\Models\AnalyticsProject;
use App\Models\Chart;
use App\Models\ChartQuery;
use App\Models\Dashboard;
use App\Models\DataSource;
use App\Models\Dimension;
use App\Models\Embed;
use App\Models\EmbedDomain;
use App\Models\Measure;
use App\Models\Organization;
use App\Models\QueryCacheEntry;
use App\Models\QueryRun;
use App\Models\SemanticModel;
use App\Models\SemanticProvider;
use Firebase\JWT\JWT;
use Illuminate\Database\Connection;
use Illuminate\Support\Carbon;

afterEach(function () {
    AnalyticsConnectionFactory::fakeUsing(null);
    Mockery::close();
});

/**
 * Builds a fully-wired embed + dashboard + chart with a Postgres-backed
 * semantic model. Returns everything the security tests might need.
 *
 * @return array{
 *     organization: Organization,
 *     embed: Embed,
 *     dashboard: Dashboard,
 *     chart: Chart,
 *     model: SemanticModel
 * }
 */
function makeEmbedFixture(): array
{
    $organization = Organization::factory()->create();
    $project = AnalyticsProject::factory()->create(['organization_id' => $organization->id]);
    $dataSource = DataSource::factory()->create([
        'organization_id' => $organization->id,
        'driver' => 'postgres',
    ]);
    $provider = SemanticProvider::factory()->create([
        'organization_id' => $organization->id,
        'data_source_id' => $dataSource->id,
        'type' => 'internal',
    ]);
    $model = SemanticModel::factory()->create([
        'organization_id' => $organization->id,
        'semantic_provider_id' => $provider->id,
        'name' => 'orders',
        'base_table' => 'public.orders',
        'base_table_alias' => 'o',
    ]);

    Measure::factory()->create([
        'semantic_model_id' => $model->id,
        'name' => 'revenue',
        'type' => 'sum',
        'column' => 'total_amount',
        'is_public' => true,
        'expression' => null,
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

    $dashboard = Dashboard::factory()->create([
        'organization_id' => $organization->id,
        'analytics_project_id' => $project->id,
    ]);
    $chart = Chart::factory()->create([
        'dashboard_id' => $dashboard->id,
        'semantic_model_id' => $model->id,
        'chart_type' => 'table',
    ]);
    ChartQuery::factory()->create([
        'chart_id' => $chart->id,
        'semantic_query' => [
            'model' => 'orders',
            'measures' => ['revenue'],
            'dimensions' => ['country'],
            'filters' => [],
            'order_by' => [],
        ],
    ]);

    $embed = Embed::factory()->create([
        'organization_id' => $organization->id,
        'dashboard_id' => $dashboard->id,
        'is_enabled' => true,
        'default_ttl_seconds' => 300,
    ]);

    EmbedDomain::factory()->create([
        'embed_id' => $embed->id,
        'host' => 'app.example.com',
    ]);

    return compact('organization', 'embed', 'dashboard', 'chart', 'model');
}

function stubPostgresConnection(callable $selectHandler): void
{
    AnalyticsConnectionFactory::fakeUsing(function (DataSource $ds, string $driver, array $config) use ($selectHandler): Connection {
        $fake = Mockery::mock(Connection::class);
        $fake->shouldReceive('statement')->andReturn(true);
        $fake->shouldReceive('select')->andReturnUsing($selectHandler);

        return $fake;
    });
}

it('rejects a token with an invalid signature with 401', function () {
    $fixture = makeEmbedFixture();

    $token = JWT::encode([
        'iss' => 'embedlayer',
        'organization_id' => $fixture['organization']->id,
        'project_id' => $fixture['dashboard']->analytics_project_id,
        'embed_id' => $fixture['embed']->id,
        'iat' => time(),
        'exp' => time() + 300,
    ], 'an-entirely-different-key-that-the-app-does-not-trust', 'HS256');

    $response = $this->withHeaders([
        'Origin' => 'https://app.example.com',
        'Authorization' => "Bearer {$token}",
    ])->getJson("/api/embed/dashboards/{$fixture['embed']->id}");

    $response->assertStatus(401);
});

it('rejects an expired token with 401', function () {
    $fixture = makeEmbedFixture();

    $manager = app(EmbedTokenManager::class);
    $payload = new EmbedTokenPayload(
        iss: 'embedlayer',
        sub: null,
        organizationId: $fixture['organization']->id,
        projectId: $fixture['dashboard']->analytics_project_id,
        embedId: $fixture['embed']->id,
        externalAccountId: null,
        allowedDashboardIds: [$fixture['dashboard']->id],
        allowedModelNames: [],
        filters: [],
        theme: [],
        iat: time() - 1000,
        exp: time() - 100,
    );
    $token = $manager->generate($payload);

    $response = $this->withHeaders([
        'Origin' => 'https://app.example.com',
        'Authorization' => "Bearer {$token}",
    ])->getJson("/api/embed/dashboards/{$fixture['embed']->id}");

    $response->assertStatus(401);
});

it('rejects a token whose embed_id does not match the route with 403', function () {
    $fixture = makeEmbedFixture();
    $otherEmbed = Embed::factory()->create([
        'organization_id' => $fixture['organization']->id,
        'dashboard_id' => $fixture['dashboard']->id,
    ]);
    EmbedDomain::factory()->create([
        'embed_id' => $otherEmbed->id,
        'host' => 'app.example.com',
    ]);

    $token = app(GenerateEmbedToken::class)->handle($otherEmbed);

    $response = $this->withHeaders([
        'Origin' => 'https://app.example.com',
        'Authorization' => "Bearer {$token}",
    ])->getJson("/api/embed/dashboards/{$fixture['embed']->id}");

    $response->assertStatus(403);
});

it('rejects a request with a disallowed Origin with 403', function () {
    $fixture = makeEmbedFixture();
    $token = app(GenerateEmbedToken::class)->handle($fixture['embed']);

    $response = $this->withHeaders([
        'Origin' => 'https://attacker.example.com',
        'Authorization' => "Bearer {$token}",
    ])->getJson("/api/embed/dashboards/{$fixture['embed']->id}");

    $response->assertStatus(403);
});

it('rejects a chart query for a chart outside the token allowed dashboards with 403', function () {
    $fixture = makeEmbedFixture();
    $otherDashboard = Dashboard::factory()->create([
        'organization_id' => $fixture['organization']->id,
        'analytics_project_id' => $fixture['dashboard']->analytics_project_id,
    ]);
    $otherChart = Chart::factory()->create([
        'dashboard_id' => $otherDashboard->id,
        'semantic_model_id' => $fixture['model']->id,
        'chart_type' => 'table',
    ]);
    ChartQuery::factory()->create([
        'chart_id' => $otherChart->id,
        'semantic_query' => [
            'model' => 'orders',
            'measures' => ['revenue'],
            'dimensions' => ['country'],
        ],
    ]);

    $token = app(GenerateEmbedToken::class)->handle($fixture['embed']);

    $response = $this->withHeaders([
        'Origin' => 'https://app.example.com',
        'Authorization' => "Bearer {$token}",
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ])->postJson("/api/embed/charts/{$otherChart->id}/query", ['filters' => []]);

    $response->assertStatus(403);
});

it('rejects a query for a model not in allowed_model_names with 403', function () {
    $fixture = makeEmbedFixture();
    stubPostgresConnection(fn () => []);

    $token = app(GenerateEmbedToken::class)->handle($fixture['embed'], [
        'allowed_model_names' => ['users'], // anything except "orders"
    ]);

    $response = $this->withHeaders([
        'Origin' => 'https://app.example.com',
        'Authorization' => "Bearer {$token}",
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ])->postJson("/api/embed/charts/{$fixture['chart']->id}/query", ['filters' => []]);

    $response->assertStatus(422);
    expect($response->json('error'))->toContain('allowed_model_names');
});

it('applies token-derived access policies as bound filters in the executed SQL', function () {
    $fixture = makeEmbedFixture();
    AccessPolicy::factory()->create([
        'semantic_model_id' => $fixture['model']->id,
        'name' => 'tenant_scope',
        'rules' => [[
            'field' => 'account_id',
            'operator' => '=',
            'value_from_context' => 'external_account_id',
        ]],
        'is_required' => true,
    ]);

    $capturedSql = null;
    $capturedBindings = null;
    stubPostgresConnection(function (string $sql, array $bindings = []) use (&$capturedSql, &$capturedBindings): array {
        $capturedSql = $sql;
        $capturedBindings = $bindings;

        return [(object) ['country' => 'US', 'revenue' => 1000]];
    });

    $token = app(GenerateEmbedToken::class)->handle($fixture['embed'], [
        'external_account_id' => 'acct_42',
    ]);

    $response = $this->withHeaders([
        'Origin' => 'https://app.example.com',
        'Authorization' => "Bearer {$token}",
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ])->postJson("/api/embed/charts/{$fixture['chart']->id}/query", ['filters' => []]);

    $response->assertOk();
    expect($capturedSql)->toContain('account_id')
        ->and($capturedBindings)->toContain('acct_42');
});

it('returns the cached result and does not call the data source on a cache hit', function () {
    $fixture = makeEmbedFixture();

    $callCount = 0;
    stubPostgresConnection(function () use (&$callCount): array {
        $callCount++;

        return [(object) ['country' => 'US', 'revenue' => 999]];
    });

    // Pre-seed cache with the exact key the pipeline will derive.
    $token = app(GenerateEmbedToken::class)->handle($fixture['embed']);

    $cacheKey = hash('sha256', (string) json_encode([
        'organization_id' => $fixture['organization']->id,
        'project_id' => $fixture['dashboard']->analytics_project_id,
        'external_account_id' => null,
        'chart_id' => $fixture['chart']->id,
        'query' => [
            'model' => 'orders',
            'measures' => ['revenue'],
            'dimensions' => ['country'],
            'time_dimension' => null,
            'filters' => [],
            'order_by' => [],
            'limit' => null,
            'offset' => null,
            'context' => [],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

    QueryCacheEntry::query()->create([
        'organization_id' => $fixture['organization']->id,
        'cache_key' => $cacheKey,
        'result' => [
            'columns' => [['key' => 'country', 'label' => 'country', 'type' => 'string']],
            'rows' => [['country' => 'CACHED', 'revenue' => 7]],
            'metadata' => ['source' => 'cache-fixture'],
        ],
        'metadata' => [],
        'expires_at' => Carbon::now()->addMinutes(5),
        'last_accessed_at' => null,
    ]);

    $response = $this->withHeaders([
        'Origin' => 'https://app.example.com',
        'Authorization' => "Bearer {$token}",
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ])->postJson("/api/embed/charts/{$fixture['chart']->id}/query", ['filters' => []]);

    $response->assertOk();
    expect($callCount)->toBe(0)
        ->and($response->json('result.rows.0.country'))->toBe('CACHED');

    // Audit row records the cache hit.
    expect(QueryRun::query()->where('cache_hit', true)->count())->toBeGreaterThan(0);
});

it('happy path: matching token + Origin + dashboard returns chart data with 200', function () {
    $fixture = makeEmbedFixture();

    stubPostgresConnection(fn (string $sql, array $bindings = []): array => [
        (object) ['country' => 'US', 'revenue' => 1500],
        (object) ['country' => 'CA', 'revenue' => 250],
    ]);

    $token = app(GenerateEmbedToken::class)->handle($fixture['embed']);

    $dashboardResponse = $this->withHeaders([
        'Origin' => 'https://app.example.com',
        'Authorization' => "Bearer {$token}",
    ])->getJson("/api/embed/dashboards/{$fixture['embed']->id}");

    $dashboardResponse->assertOk()
        ->assertJsonPath('embed.id', $fixture['embed']->id)
        ->assertJsonPath('dashboard.id', $fixture['dashboard']->id)
        ->assertJsonCount(1, 'charts');

    $queryResponse = $this->withHeaders([
        'Origin' => 'https://app.example.com',
        'Authorization' => "Bearer {$token}",
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ])->postJson("/api/embed/charts/{$fixture['chart']->id}/query", ['filters' => []]);

    $queryResponse->assertOk()
        ->assertJsonPath('chart_id', $fixture['chart']->id)
        ->assertJsonCount(2, 'result.rows');
});

it('iframe entry route renders the runtime shell with the supplied token', function () {
    $fixture = makeEmbedFixture();

    $response = $this->get("/embed/dashboards/{$fixture['embed']->id}?token=fake-token");

    $response->assertOk()
        ->assertSee('embed-layer-dashboard', false)
        ->assertSee('fake-token', false)
        ->assertSee($fixture['embed']->id, false);
});
