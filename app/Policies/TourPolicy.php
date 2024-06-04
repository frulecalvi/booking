<?php

namespace App\Policies;

use App\Models\User;

class TourPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function viewAny(?User $user)
    {
        return true;
    }

    public function create(User $user)
    {
        return $user->hasRole('Admin');
    }
}
