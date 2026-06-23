<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SemanticModel;
use App\Models\User;
use App\Policies\Concerns\ScopesByOrganization;

final class SemanticModelPolicy
{
    use ScopesByOrganization;

    public function viewAny(User $user): bool
    {
        return $user->organization_id !== null;
    }

    public function view(User $user, SemanticModel $semanticModel): bool
    {
        return $this->userOwnsOrganization($user, $semanticModel);
    }

    public function create(User $user): bool
    {
        return $user->organization_id !== null;
    }

    public function update(User $user, SemanticModel $semanticModel): bool
    {
        return $this->userOwnsOrganization($user, $semanticModel);
    }

    public function delete(User $user, SemanticModel $semanticModel): bool
    {
        return $this->userOwnsOrganization($user, $semanticModel);
    }
}
