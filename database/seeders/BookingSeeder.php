<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Price;
use App\Models\Schedule;
use App\Models\Ticket;
use App\Models\Tour;
use App\States\Schedule\Active as ScheduleActive;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tours = Tour::all();

        foreach ($tours as $tour) {
            $lastPrice = $tour->prices->last();

            if ($lastEvent = $tour->events->last()) {
                $lastEventSchedule = Schedule::findOrFail($lastEvent->schedule_id);
                $booking = Booking::factory()
                    ->for($lastEvent)
                    ->for($lastEventSchedule)
                    ->for($tour, 'bookingable')
                    ->create();

                Ticket::factory(3)
                    ->for($booking)
                    ->for($lastPrice)
                    ->create();
            }
        }
    }
}
