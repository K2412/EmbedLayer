<?php

declare(strict_types=1);

use App\Analytics\Actions\SemanticModels\AddAccessPolicy;
use App\Analytics\Actions\SemanticModels\AddDimensionToModel;
use App\Analytics\Actions\SemanticModels\AddJoinToModel;
use App\Analytics\Actions\SemanticModels\AddMeasureToModel;
use App\Analytics\Actions\SemanticModels\ValidateSemanticModel;
use App\Analytics\Support\Exceptions\SemanticModelValidationException;
use App\Models\Measure;
use App\Models\Organization;
use App\Models\SemanticModel;
use App\Models\SemanticProvider;

function freshModel(): SemanticModel
{
    $org = Organization::factory()->create();
    $provider = SemanticProvider::factory()->create(['organization_id' => $org->id]);

    return SemanticModel::factory()->create([
        'organization_id' => $org->id,
        'semantic_provider_id' => $provider->id,
        'base_table' => 'public.orders',
    ]);
}

it('rejects measure with unknown type', function () {
    expect(fn () => (new AddMeasureToModel)->handle(freshModel(), [
        'name' => 'foo', 'label' => 'Foo', 'type' => 'mode',
    ]))->toThrow(SemanticModelValidationException::class);
});

it('rejects sum measure with no column', function () {
    expect(fn () => (new AddMeasureToModel)->handle(freshModel(), [
        'name' => 'rev', 'label' => 'R', 'type' => 'sum',
    ]))->toThrow(SemanticModelValidationException::class, 'requires a column');
});

it('rejects ratio measure with no expression', function () {
    expect(fn () => (new AddMeasureToModel)->handle(freshModel(), [
        'name' => 'aov', 'label' => 'AOV', 'type' => 'ratio',
    ]))->toThrow(SemanticModelValidationException::class);
});

it('accepts ratio measure with numerator + denominator', function () {
    $model = freshModel();
    $measure = (new AddMeasureToModel)->handle($model, [
        'name' => 'aov',
        'label' => 'AOV',
        'type' => 'ratio',
        'expression' => ['numerator' => 'revenue', 'denominator' => 'orders_count'],
    ]);

    expect($measure->type)->toBe('ratio');
});

it('requires allowed_time_grains for time dimensions', function () {
    expect(fn () => (new AddDimensionToModel)->handle(freshModel(), [
        'name' => 'created_at', 'label' => 'Created', 'type' => 'time', 'column' => 'created_at',
    ]))->toThrow(SemanticModelValidationException::class, 'allowed_time_grains');
});

it('rejects unknown time grains', function () {
    expect(fn () => (new AddDimensionToModel)->handle(freshModel(), [
        'name' => 'created_at', 'label' => 'Created', 'type' => 'time', 'column' => 'created_at',
        'allowed_time_grains' => ['fortnight'],
    ]))->toThrow(SemanticModelValidationException::class, 'unknown time grain');
});

it('rejects allowed_time_grains on non-time dimensions', function () {
    expect(fn () => (new AddDimensionToModel)->handle(freshModel(), [
        'name' => 'country', 'label' => 'Country', 'type' => 'string', 'column' => 'shipping_country',
        'allowed_time_grains' => ['day'],
    ]))->toThrow(SemanticModelValidationException::class, 'only valid for time');
});

it('rejects join with unsupported relationship', function () {
    expect(fn () => (new AddJoinToModel)->handle(freshModel(), [
        'name' => 'x', 'left_table_alias' => 'a', 'left_column' => 'b',
        'right_table' => 'c', 'right_table_alias' => 'd', 'right_column' => 'e',
        'relationship' => 'many_to_many',
    ]))->toThrow(SemanticModelValidationException::class);
});

it('rejects join with unsupported type', function () {
    expect(fn () => (new AddJoinToModel)->handle(freshModel(), [
        'name' => 'x', 'left_table_alias' => 'a', 'left_column' => 'b',
        'right_table' => 'c', 'right_table_alias' => 'd', 'right_column' => 'e',
        'relationship' => 'many_to_one', 'type' => 'cross',
    ]))->toThrow(SemanticModelValidationException::class);
});

it('rejects access policy with literal value', function () {
    expect(fn () => (new AddAccessPolicy)->handle(freshModel(), [
        'name' => 'tenant', 'rules' => [['field' => 'account_id', 'value' => 'acct_x']],
    ]))->toThrow(SemanticModelValidationException::class, 'literal');
});

it('rejects access policy missing value_from_context', function () {
    expect(fn () => (new AddAccessPolicy)->handle(freshModel(), [
        'name' => 'tenant', 'rules' => [['field' => 'account_id']],
    ]))->toThrow(SemanticModelValidationException::class, 'value_from_context');
});

it('accepts valid access policy', function () {
    $policy = (new AddAccessPolicy)->handle(freshModel(), [
        'name' => 'tenant_isolation',
        'rules' => [['field' => 'account_id', 'operator' => '=', 'value_from_context' => 'external_account_id']],
    ]);

    expect($policy->name)->toBe('tenant_isolation')
        ->and($policy->is_required)->toBeTrue();
});

it('ValidateSemanticModel: empty model has errors', function () {
    $errors = (new ValidateSemanticModel)->handle(freshModel());

    expect($errors)->toContain('semantic model has no measures; at least one is required');
});

it('ValidateSemanticModel: detects duplicate measure names from in-memory data', function () {
    $model = freshModel();
    (new AddMeasureToModel)->handle($model, ['name' => 'revenue', 'label' => 'R', 'type' => 'sum', 'column' => 'total']);

    // Hand-build a duplicate collection without persisting (the DB unique
    // index prevents persisted duplicates — validator must still catch
    // them in case the data set is rebuilt or migrated).
    $model->setRelation('measures', collect([
        ...$model->measures->all(),
        new Measure(['semantic_model_id' => $model->id, 'name' => 'revenue', 'label' => 'Dup', 'type' => 'count', 'column' => 'id']),
    ]));

    $errors = (new ValidateSemanticModel)->handle($model);

    expect($errors)->toContain('duplicate measure name `revenue`');
});

it('ValidateSemanticModel: ratio measure missing numerator/denominator flagged', function () {
    $model = freshModel();
    (new AddMeasureToModel)->handle($model, ['name' => 'rev', 'label' => 'R', 'type' => 'sum', 'column' => 'total']);

    Measure::query()->create([
        'semantic_model_id' => $model->id,
        'name' => 'aov',
        'label' => 'AOV',
        'type' => 'ratio',
        'expression' => ['numerator' => 'rev', 'denominator' => 'orders_count'],
    ]);

    $errors = (new ValidateSemanticModel)->handle($model);

    expect($errors)->toContain('ratio measure `aov` references unknown measure `orders_count`');
});

it('ValidateSemanticModel: well-formed model has no errors', function () {
    $model = freshModel();
    (new AddMeasureToModel)->handle($model, ['name' => 'rev', 'label' => 'R', 'type' => 'sum', 'column' => 'total']);
    (new AddDimensionToModel)->handle($model, ['name' => 'country', 'label' => 'C', 'type' => 'string', 'column' => 'country']);

    expect((new ValidateSemanticModel)->handle($model))->toBe([]);
});
