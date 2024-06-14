<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\Tour;
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

    public function view(?User $user)
    {
        return true;
    }

    public function create(User $user)
    {
        return $user->hasRole('Admin');
    }

    public function delete(User $user)
    {
        return $user->hasRole('Admin');
    }

    public function update(User $user)
    {
        return $user->hasRole('Admin');
    }

    public function viewEvents(?User $user, Tour $tour)
    {
        return true;
    }

    public function viewPrices(?User $user, Tour $tour)
    {
        return true;
    }
}
