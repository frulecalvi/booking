<?php

namespace App\States\Booking;

use Spatie\ModelStates\Attributes\AllowTransition;
use Spatie\ModelStates\Attributes\DefaultState;
use Spatie\ModelStates\Attributes\RegisterState;
use Spatie\ModelStates\State;

#[
    DefaultState(Inactive::class),
    RegisterState(Active::class),
]
abstract class BookingState extends State
{
    //
}
