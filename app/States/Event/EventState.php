<?php

namespace App\States\Event;

use Spatie\ModelStates\Attributes\AllowTransition;
use Spatie\ModelStates\Attributes\DefaultState;
use Spatie\ModelStates\Attributes\RegisterState;
use Spatie\ModelStates\State;

#[
    DefaultState(Active::class),
    RegisterState(Inactive::class),
]
abstract class EventState extends State
{
    //
}
