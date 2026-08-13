<?php

namespace App\Policies;

use App\Models\ExtraService;
use App\Models\User;
use App\Support\PlanHelper;

class ExtraServicePolicy
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
    public function view(User $user, ExtraService $extraService): bool
    {
        return $user->id === $extraService->user_id;
    }

    /**
     * Determine whether the user can create models (with plan limit validation).
     */
    public function create(User $user): bool
    {
        return PlanHelper::canCreateExtraService($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ExtraService $extraService): bool
    {
        return $user->id === $extraService->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ExtraService $extraService): bool
    {
        return $user->id === $extraService->user_id;
    }
}
