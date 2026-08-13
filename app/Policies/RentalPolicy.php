<?php

namespace App\Policies;

use App\Models\Rental;
use App\Models\User;
use App\Support\PlanHelper;

class RentalPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Rental $rental): bool
    {
        return $user->id === $rental->user_id;
    }

    public function create(User $user): bool
    {
        return PlanHelper::canCreateRental($user);
    }

    public function update(User $user, Rental $rental): bool
    {
        return $user->id === $rental->user_id;
    }

    public function delete(User $user, Rental $rental): bool
    {
        return $user->id === $rental->user_id;
    }
}
