<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
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

    public function view(?User $user)
    {
        return true;
    }

    // public function viewPrices(?User $user, Event $event)
    // {
    //     // dd('dsadas');
    //     return true;
    // }
}
