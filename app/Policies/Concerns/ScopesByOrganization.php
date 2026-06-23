<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait ScopesByOrganization
{
    protected function userOwnsOrganization(User $user, Model $model): bool
    {
        $organizationId = $model->getAttribute('organization_id');

        if (! is_string($organizationId)) {
            return false;
        }

        return $user->belongsToOrganization($organizationId);
    }
}
