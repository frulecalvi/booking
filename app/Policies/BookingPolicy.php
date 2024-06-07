<?php

namespace App\Policies;

use App\Models\User;

class BookingPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function create(?User $user)
    {
        return true;
    }

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user)
    {
        return true;
    }

    public function delete(User $user)
    {
        return false;
    }

    public function update(User $user)
    {
        return true;
    }
}
