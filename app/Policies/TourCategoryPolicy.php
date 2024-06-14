<?php

namespace App\Policies;

use App\Models\User;

class TourCategoryPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function view(?User $user)
    {
        return true;
    }
}
