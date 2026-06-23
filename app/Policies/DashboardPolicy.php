<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Dashboard;
use App\Models\User;
use App\Policies\Concerns\ScopesByOrganization;

final class DashboardPolicy
{
    use ScopesByOrganization;

    public function viewAny(User $user): bool
    {
        return $user->organization_id !== null;
    }

    public function view(User $user, Dashboard $dashboard): bool
    {
        return $this->userOwnsOrganization($user, $dashboard);
    }

    public function create(User $user): bool
    {
        return $user->organization_id !== null;
    }

    public function update(User $user, Dashboard $dashboard): bool
    {
        return $this->userOwnsOrganization($user, $dashboard);
    }

    public function publish(User $user, Dashboard $dashboard): bool
    {
        return $this->userOwnsOrganization($user, $dashboard);
    }

    public function delete(User $user, Dashboard $dashboard): bool
    {
        return $this->userOwnsOrganization($user, $dashboard);
    }
}
