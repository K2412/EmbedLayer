<?php

declare(strict_types=1);

namespace App\Analytics\Actions\Dashboards;

use App\Models\Dashboard;
use Illuminate\Support\Facades\Date;

final readonly class PublishDashboard
{
    public function handle(Dashboard $dashboard): Dashboard
    {
        $dashboard->update([
            'is_published' => true,
            'published_at' => Date::now(),
        ]);

        return $dashboard->refresh();
    }
}
