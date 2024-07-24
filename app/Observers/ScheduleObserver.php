<?php

namespace App\Observers;

use App\Jobs\CreateScheduleEvents;
use App\Models\Schedule;

class ScheduleObserver
{
    public function created(Schedule $schedule): void
    {
        CreateScheduleEvents::dispatch($schedule);
    }
}
