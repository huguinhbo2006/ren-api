<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;
use App\Support\PlanHelper;

class AssetPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Asset $asset): bool
    {
        return $user->id === $asset->user_id;
    }

    /**
     * Determine whether the user can create models (with plan limit validation: max 3 in Free).
     */
    public function create(User $user): bool
    {
        return PlanHelper::canCreateAsset($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Asset $asset): bool
    {
        return $user->id === $asset->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Asset $asset): bool
    {
        return $user->id === $asset->user_id;
    }
}
