<?php

namespace App\Policies;

use App\Models\Price;
use App\Models\User;

class PricePolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function create(User $user)
    {
        return $user->hasRole('Admin');
    }

    public function update(User $user, Price $price)
    {
        return $user->hasRole('Admin');
    }

    public function delete(User $user, Price $price)
    {
        return $user->hasRole('Admin');
    }
}
