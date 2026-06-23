<?php

declare(strict_types=1);

use App\Models\AnalyticsProject;
use App\Models\Organization;
use App\Models\Team;

it('creates an organization with a ULID primary key', function () {
    $org = Organization::factory()->create(['name' => 'Acme']);

    expect($org->id)->toBeString()->toHaveLength(26)
        ->and($org->name)->toBe('Acme');
});

it('associates a team with an organization', function () {
    $org = Organization::factory()->create();
    $team = Team::factory()->for($org)->create(['name' => 'Platform']);

    expect($team->organization_id)->toBe($org->id)
        ->and($team->organization->name)->toBe($org->name)
        ->and($org->fresh()->teams->pluck('id')->all())->toBe([$team->id]);
});

it('associates an analytics project with an organization', function () {
    $org = Organization::factory()->create();
    $project = AnalyticsProject::factory()->for($org)->create(['name' => 'Marketing']);

    expect($project->organization_id)->toBe($org->id)
        ->and($org->fresh()->analyticsProjects->pluck('id')->all())->toBe([$project->id]);
});

it('cascades deletion from organization to teams and projects', function () {
    $org = Organization::factory()
        ->has(Team::factory()->count(2))
        ->has(AnalyticsProject::factory()->count(3), 'analyticsProjects')
        ->create();

    expect(Team::query()->where('organization_id', $org->id)->count())->toBe(2)
        ->and(AnalyticsProject::query()->where('organization_id', $org->id)->count())->toBe(3);

    $org->delete();

    expect(Team::query()->where('organization_id', $org->id)->count())->toBe(0)
        ->and(AnalyticsProject::query()->where('organization_id', $org->id)->count())->toBe(0);
});
