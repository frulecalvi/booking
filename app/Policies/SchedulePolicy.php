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
        return $user ?? false;
    }
}
