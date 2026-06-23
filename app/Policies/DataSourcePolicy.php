<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DataSource;
use App\Models\User;
use App\Policies\Concerns\ScopesByOrganization;

final class DataSourcePolicy
{
    use ScopesByOrganization;

    public function viewAny(User $user): bool
    {
        return $user->organization_id !== null;
    }

    public function view(User $user, DataSource $dataSource): bool
    {
        return $this->userOwnsOrganization($user, $dataSource);
    }

    public function create(User $user): bool
    {
        return $user->organization_id !== null;
    }

    public function update(User $user, DataSource $dataSource): bool
    {
        return $this->userOwnsOrganization($user, $dataSource);
    }

    public function delete(User $user, DataSource $dataSource): bool
    {
        return $this->userOwnsOrganization($user, $dataSource);
    }
}
