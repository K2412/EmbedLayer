<?php

declare(strict_types=1);

use App\Models\Dashboard;
use App\Models\DataSource;
use App\Models\Embed;
use App\Models\Organization;
use App\Models\SemanticModel;
use App\Models\User;

it('allows a user to access only their own organization\'s data sources', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    $user = User::factory()->create(['organization_id' => $orgA->id]);
    $own = DataSource::factory()->create(['organization_id' => $orgA->id]);
    $foreign = DataSource::factory()->create(['organization_id' => $orgB->id]);

    expect($user->can('view', $own))->toBeTrue()
        ->and($user->can('update', $own))->toBeTrue()
        ->and($user->can('delete', $own))->toBeTrue()
        ->and($user->can('view', $foreign))->toBeFalse()
        ->and($user->can('update', $foreign))->toBeFalse()
        ->and($user->can('delete', $foreign))->toBeFalse();
});

it('denies any access to a user without an organization', function () {
    $orphan = User::factory()->create(['organization_id' => null]);
    $ds = DataSource::factory()->create();

    expect($orphan->can('viewAny', DataSource::class))->toBeFalse()
        ->and($orphan->can('view', $ds))->toBeFalse()
        ->and($orphan->can('create', DataSource::class))->toBeFalse();
});

it('scopes semantic models, dashboards, and embeds by organization_id', function () {
    $org = Organization::factory()->create();
    $other = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);

    $sm = SemanticModel::factory()->create(['organization_id' => $org->id]);
    $smForeign = SemanticModel::factory()->create(['organization_id' => $other->id]);

    $dash = Dashboard::factory()->create(['organization_id' => $org->id]);
    $dashForeign = Dashboard::factory()->create(['organization_id' => $other->id]);

    $embed = Embed::factory()->create(['organization_id' => $org->id]);
    $embedForeign = Embed::factory()->create(['organization_id' => $other->id]);

    expect($user->can('view', $sm))->toBeTrue()
        ->and($user->can('view', $smForeign))->toBeFalse()
        ->and($user->can('update', $dash))->toBeTrue()
        ->and($user->can('publish', $dash))->toBeTrue()
        ->and($user->can('update', $dashForeign))->toBeFalse()
        ->and($user->can('publish', $dashForeign))->toBeFalse()
        ->and($user->can('generateToken', $embed))->toBeTrue()
        ->and($user->can('generateToken', $embedForeign))->toBeFalse();
});
