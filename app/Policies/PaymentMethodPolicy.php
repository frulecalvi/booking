<?php

namespace App\Policies;

use App\Models\User;

class PaymentMethodPolicy
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
        return $user->hasRole('Admin');
    }

    public function view(User $user)
    {
        return $user->hasRole('Admin');
    }

    public function create(User $user)
    {
        return $user->hasRole('Admin');
    }
}
