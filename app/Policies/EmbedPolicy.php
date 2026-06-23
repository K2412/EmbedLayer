<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Embed;
use App\Models\User;
use App\Policies\Concerns\ScopesByOrganization;

final class EmbedPolicy
{
    use ScopesByOrganization;

    public function viewAny(User $user): bool
    {
        return $user->organization_id !== null;
    }

    public function view(User $user, Embed $embed): bool
    {
        return $this->userOwnsOrganization($user, $embed);
    }

    public function create(User $user): bool
    {
        return $user->organization_id !== null;
    }

    public function update(User $user, Embed $embed): bool
    {
        return $this->userOwnsOrganization($user, $embed);
    }

    public function generateToken(User $user, Embed $embed): bool
    {
        return $this->userOwnsOrganization($user, $embed);
    }

    public function delete(User $user, Embed $embed): bool
    {
        return $this->userOwnsOrganization($user, $embed);
    }
}
