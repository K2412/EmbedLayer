<?php

declare(strict_types=1);

use App\Analytics\Actions\Dashboards\AddChartToDashboard;
use App\Analytics\Actions\Dashboards\CreateDashboard;
use App\Analytics\Actions\Dashboards\PublishDashboard;
use App\Analytics\Actions\DataSources\CreateDataSource;
use App\Analytics\Actions\SemanticModels\AddDimensionToModel;
use App\Analytics\Actions\SemanticModels\AddJoinToModel;
use App\Analytics\Actions\SemanticModels\AddMeasureToModel;
use App\Analytics\Actions\SemanticModels\CreateSemanticModel;
use App\Analytics\Security\CredentialVault;
use App\Analytics\Support\Exceptions\CredentialVaultException;
use App\Models\AnalyticsProject;
use App\Models\Organization;
use App\Models\SemanticModel;
use App\Models\SemanticProvider;
use Illuminate\Encryption\Encrypter;

beforeEach(function () {
    config()->set('embedlayer.credential_encryption_key', Encrypter::generateKey('aes-256-gcm'));
    config()->set('embedlayer.previous_credential_encryption_keys', []);
});

it('CreateDataSource persists an encrypted config blob', function () {
    $organization = Organization::factory()->create();
    $action = new CreateDataSource(CredentialVault::fromConfig());

    $dataSource = $action->handle(
        organization: $organization,
        name: 'Warehouse',
        driver: 'postgres',
        config: ['host' => 'db.example.com', 'password' => 'secret-xyz'],
    );

    expect($dataSource->organization_id)->toBe($organization->id)
        ->and($dataSource->driver)->toBe('postgres')
        ->and((string) $dataSource->encrypted_config)->not->toContain('secret-xyz')
        ->and(CredentialVault::fromConfig()->decryptConfig($dataSource->encrypted_config))
        ->toMatchArray(['host' => 'db.example.com', 'password' => 'secret-xyz']);
});

it('CreateDataSource fails fast if no encryption key is configured', function () {
    config()->set('embedlayer.credential_encryption_key', '');

    expect(fn () => CredentialVault::fromConfig())->toThrow(CredentialVaultException::class);
});

it('CreateSemanticModel + AddMeasureToModel + AddDimensionToModel + AddJoinToModel scaffold a model', function () {
    $organization = Organization::factory()->create();
    $provider = SemanticProvider::factory()->create([
        'organization_id' => $organization->id,
        'type' => 'internal',
    ]);

    $model = (new CreateSemanticModel)->handle(
        provider: $provider,
        name: 'orders',
        label: 'Orders',
        baseTable: 'public.orders',
        baseTableAlias: 'orders',
    );

    expect($model)->toBeInstanceOf(SemanticModel::class);

    $measure = (new AddMeasureToModel)->handle($model, [
        'name' => 'revenue',
        'label' => 'Revenue',
        'type' => 'sum',
        'column' => 'total_amount',
        'format' => 'currency',
    ]);

    $dimension = (new AddDimensionToModel)->handle($model, [
        'name' => 'country',
        'label' => 'Country',
        'type' => 'string',
        'column' => 'shipping_country',
    ]);

    $join = (new AddJoinToModel)->handle($model, [
        'name' => 'orders_to_customers',
        'left_table_alias' => 'orders',
        'left_column' => 'customer_id',
        'right_table' => 'public.customers',
        'right_table_alias' => 'customers',
        'right_column' => 'id',
        'relationship' => 'many_to_one',
    ]);

    expect($model->fresh()->measures)->toHaveCount(1)
        ->and($model->fresh()->dimensions)->toHaveCount(1)
        ->and($model->fresh()->joins)->toHaveCount(1)
        ->and($measure->semantic_model_id)->toBe($model->id)
        ->and($dimension->semantic_model_id)->toBe($model->id)
        ->and($join->semantic_model_id)->toBe($model->id);
});

it('CreateDashboard, AddChartToDashboard, PublishDashboard flow', function () {
    $project = AnalyticsProject::factory()->create();
    $provider = SemanticProvider::factory()->create(['organization_id' => $project->organization_id]);
    $model = SemanticModel::factory()->create([
        'organization_id' => $project->organization_id,
        'semantic_provider_id' => $provider->id,
    ]);

    $dashboard = (new CreateDashboard)->handle($project, name: 'Ops', slug: 'ops');

    expect($dashboard->is_published)->toBeFalse();

    $chart = app(AddChartToDashboard::class)->handle(
        dashboard: $dashboard,
        model: $model,
        name: 'Total Revenue',
        chartType: 'number_card',
        semanticQuery: ['model' => 'orders', 'measures' => ['revenue']],
    );

    expect($chart->chartQuery)->not->toBeNull()
        ->and($chart->chartQuery->semantic_query)->toMatchArray(['model' => 'orders', 'measures' => ['revenue']]);

    $published = (new PublishDashboard)->handle($dashboard);

    expect($published->is_published)->toBeTrue()
        ->and($published->published_at)->not->toBeNull();
});
