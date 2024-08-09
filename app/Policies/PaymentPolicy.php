<?php

namespace App\Policies;

use App\Models\User;

class PaymentPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function create(User $user): bool
    {
        return false;
    }
}
