<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\Schedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateScheduleEvents implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $schedule;

    /**
     * Create a new job instance.
     */
    public function __construct(Schedule $schedule)
    {
        $this->schedule = $schedule;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (! $this->schedule->scheduleable)
            return;

        $events = [];

        if ($this->schedule->period === 'once') {
            $events[] = new Event([
                'date_time' => "{$this->schedule->date} {$this->schedule->time}",
            ]);

            // dd($this->schedule->date);

        } elseif ($this->schedule->period === 'weekly') {
            $futureEventsDates = getAllWeekdayDatesUntil($this->schedule->day, $this->schedule->scheduleable->end_date);

            // dd($this->schedule->scheduleable->end_date);


            foreach ($futureEventsDates as $date) {
                $events[] = new Event([
                    'date_time' => "{$date} {$this->schedule->time}",
                ]);
            }

            // dd($futureEventsDates);

        }

        if ($events) {
            $this->schedule->events()->saveMany($events);
            $this->schedule->scheduleable->events()->saveMany($events);
        }
    }
}
