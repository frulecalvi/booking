<?php

namespace App\Policies;

use App\Models\User;

class SchedulePolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user)
    {
        return true;
    }

    public function create(User $user)
    {
        return $user->hasRole('Admin') ?: false;
    }

    public function delete(User $user)
    {
        return $user->hasRole('Admin') ?: false;
    }

    public function update(User $user)
    {
        return $user->hasRole('Admin') ?: false;
    }
}
