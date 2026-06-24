<?php

declare(strict_types=1);

use App\Analytics\Charts\ChartTypeRegistry;
use App\Analytics\Support\Exceptions\SemanticModelValidationException;
use Tests\TestCase;

uses(TestCase::class);

it('exposes the four V1 chart types', function () {
    expect((new ChartTypeRegistry)->keys())
        ->toBe(['number_card', 'bar_chart', 'line_chart', 'table']);
});

it('looks up by key', function () {
    $type = (new ChartTypeRegistry)->get('bar_chart');

    expect($type->label)->toBe('Bar chart');
});

it('throws for unknown chart type', function () {
    expect(fn () => (new ChartTypeRegistry)->get('pie'))
        ->toThrow(SemanticModelValidationException::class, 'unknown chart_type');
});

it('number_card requires exactly one measure and zero dimensions', function () {
    $type = (new ChartTypeRegistry)->get('number_card');

    expect($type->validateShape(1, 0, false))->toBe([])
        ->and($type->validateShape(2, 0, false))->not->toBeEmpty()
        ->and($type->validateShape(0, 0, false))->not->toBeEmpty()
        ->and($type->validateShape(1, 1, false))->not->toBeEmpty();
});

it('line_chart requires a time dimension', function () {
    $type = (new ChartTypeRegistry)->get('line_chart');

    expect($type->validateShape(1, 0, false))->not->toBeEmpty()
        ->and($type->validateShape(1, 0, true))->toBe([]);
});

it('table accepts any shape', function () {
    $type = (new ChartTypeRegistry)->get('table');

    expect($type->validateShape(0, 0, false))->toBe([])
        ->and($type->validateShape(5, 4, true))->toBe([]);
});
