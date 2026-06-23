<?php

declare(strict_types=1);

namespace App\Analytics\Actions\Dashboards;

use App\Models\AnalyticsProject;
use App\Models\Dashboard;

final readonly class CreateDashboard
{
    public function handle(
        AnalyticsProject $project,
        string $name,
        string $slug,
        ?string $description = null,
    ): Dashboard {
        return Dashboard::query()->create([
            'organization_id' => $project->organization_id,
            'analytics_project_id' => $project->id,
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
        ])->refresh();
    }
}
