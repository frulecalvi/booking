<?php

namespace App\Policies;

use App\Models\User;

class TicketPolicy
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
}
