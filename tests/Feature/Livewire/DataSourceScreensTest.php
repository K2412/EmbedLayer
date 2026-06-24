<?php

declare(strict_types=1);

use App\Analytics\DataSources\Support\AnalyticsConnectionFactory;
use App\Analytics\Security\CredentialVault;
use App\Livewire\Analytics\CreateDataSource;
use App\Livewire\Analytics\DataSourceIndex;
use App\Livewire\Analytics\DataSourceShow;
use App\Models\DataSource;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Connection;
use Livewire\Livewire;

afterEach(function () {
    AnalyticsConnectionFactory::fakeUsing(null);
});

it('redirects guests away from the index screen', function () {
    $this->get(route('analytics.data-sources.index'))
        ->assertRedirect(route('login'));
});

it('forbids users without an organization on the index screen', function () {
    $orphan = User::factory()->create(['organization_id' => null]);
    $this->actingAs($orphan);

    $this->get(route('analytics.data-sources.index'))
        ->assertForbidden();
});

it('forbids users without an organization on the create screen', function () {
    $orphan = User::factory()->create(['organization_id' => null]);
    $this->actingAs($orphan);

    $this->get(route('analytics.data-sources.create'))
        ->assertForbidden();
});

it('renders the index page over HTTP', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);

    $this->actingAs($user);

    $this->get(route('analytics.data-sources.index'))
        ->assertOk()
        ->assertSee('Data sources');
});

it('renders the create page over HTTP', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);

    $this->actingAs($user);

    $this->get(route('analytics.data-sources.create'))
        ->assertOk()
        ->assertSee('New data source');
});

it('lists only data sources from the user\'s organization', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    $user = User::factory()->create(['organization_id' => $orgA->id]);
    $own = DataSource::factory()->create(['organization_id' => $orgA->id, 'name' => 'Mine Warehouse']);
    $foreign = DataSource::factory()->create(['organization_id' => $orgB->id, 'name' => 'Foreign Warehouse']);

    $this->actingAs($user);

    Livewire::test(DataSourceIndex::class)
        ->assertSee('Mine Warehouse')
        ->assertDontSee('Foreign Warehouse');
});

it('shows the empty state when the organization has no data sources', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);

    $this->actingAs($user);

    Livewire::test(DataSourceIndex::class)
        ->assertSee('No data sources yet');
});

it('persists a postgres data source with an encrypted config', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);

    $this->actingAs($user);

    Livewire::test(CreateDataSource::class)
        ->set('name', 'Production Postgres')
        ->set('driver', 'postgres')
        ->set('host', 'db.example.com')
        ->set('port', '5432')
        ->set('database', 'analytics')
        ->set('username', 'reader')
        ->set('password', 's3cret!')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $dataSource = DataSource::query()->where('name', 'Production Postgres')->firstOrFail();

    expect($dataSource->organization_id)->toBe($org->id)
        ->and($dataSource->driver)->toBe('postgres')
        ->and($dataSource->encrypted_config)->not->toBe('')
        ->and($dataSource->encrypted_config)->not->toContain('s3cret!');

    $decrypted = app(CredentialVault::class)->decryptDataSourceConfig($dataSource);

    expect($decrypted)->toMatchArray([
        'host' => 'db.example.com',
        'port' => 5432,
        'database' => 'analytics',
        'username' => 'reader',
        'password' => 's3cret!',
    ]);
});

it('validates required fields when creating a data source', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);

    $this->actingAs($user);

    Livewire::test(CreateDataSource::class)
        ->set('name', '')
        ->set('host', '')
        ->call('save')
        ->assertHasErrors(['name', 'host', 'database', 'username', 'password']);
});

it('denies cross-tenant access to the show screen', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    $user = User::factory()->create(['organization_id' => $orgA->id]);
    $foreign = DataSource::factory()->create(['organization_id' => $orgB->id]);

    $this->actingAs($user);

    $this->get(route('analytics.data-sources.show', $foreign))
        ->assertForbidden();
});

it('allows owning users to view the show screen', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);
    $dataSource = DataSource::factory()->create([
        'organization_id' => $org->id,
        'name' => 'My Source',
    ]);

    $this->actingAs($user);

    $this->get(route('analytics.data-sources.show', $dataSource))
        ->assertOk()
        ->assertSee('My Source');
});

it('flashes a successful connection test result', function () {
    AnalyticsConnectionFactory::fakeUsing(function (DataSource $ds, string $driver, array $config): Connection {
        $fake = Mockery::mock(Connection::class);
        $fake->shouldReceive('select')->andReturn([0 => (object) ['?column?' => 1]]);

        return $fake;
    });

    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);
    $dataSource = DataSource::factory()->create([
        'organization_id' => $org->id,
        'driver' => 'postgres',
    ]);

    $this->actingAs($user);

    Livewire::test(DataSourceShow::class, ['dataSource' => $dataSource])
        ->call('testConnection')
        ->assertDispatched('toast-show');

    expect($dataSource->fresh()->last_tested_at)->not->toBeNull();
});

it('flashes a redacted error message when the connection test fails', function () {
    AnalyticsConnectionFactory::fakeUsing(function (DataSource $ds, string $driver, array $config): Connection {
        $fake = Mockery::mock(Connection::class);
        $fake->shouldReceive('select')
            ->andThrow(new RuntimeException('connect failed: password=hunter2 host=db.example.com'));

        return $fake;
    });

    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);
    $dataSource = DataSource::factory()->create([
        'organization_id' => $org->id,
        'driver' => 'postgres',
    ]);

    $this->actingAs($user);

    Livewire::test(DataSourceShow::class, ['dataSource' => $dataSource])
        ->call('testConnection')
        ->assertDispatched('toast-show', fn (string $event, array $params) => str_contains(json_encode($params), '[REDACTED]')
            && ! str_contains(json_encode($params), 'hunter2'));
});

it('updates the cached schema when introspect is invoked', function () {
    AnalyticsConnectionFactory::fakeUsing(function (DataSource $ds, string $driver, array $config): Connection {
        $fake = Mockery::mock(Connection::class);
        $fake->shouldReceive('select')->andReturn([
            (object) ['table_schema' => 'public', 'table_name' => 'orders', 'column_name' => 'id', 'data_type' => 'bigint', 'is_nullable' => 'NO'],
            (object) ['table_schema' => 'public', 'table_name' => 'orders', 'column_name' => 'total', 'data_type' => 'numeric', 'is_nullable' => 'YES'],
        ]);

        return $fake;
    });

    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);
    $dataSource = DataSource::factory()->create([
        'organization_id' => $org->id,
        'driver' => 'postgres',
        'last_introspected_schema' => null,
    ]);

    $this->actingAs($user);

    Livewire::test(DataSourceShow::class, ['dataSource' => $dataSource])
        ->call('introspect')
        ->assertSee('orders');

    $fresh = $dataSource->fresh();

    expect($fresh->last_introspected_at)->not->toBeNull()
        ->and($fresh->last_introspected_schema['tables'][0]['name'])->toBe('orders')
        ->and($fresh->last_introspected_schema['tables'][0]['columns'])->toHaveCount(2);
});

it('denies test connection calls when the user no longer owns the data source', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $orgA->id]);
    $dataSource = DataSource::factory()->create(['organization_id' => $orgA->id, 'driver' => 'postgres']);

    $this->actingAs($user);

    $component = Livewire::test(DataSourceShow::class, ['dataSource' => $dataSource]);

    $dataSource->forceFill(['organization_id' => $orgB->id])->save();

    $component->call('testConnection')->assertStatus(403);
});
