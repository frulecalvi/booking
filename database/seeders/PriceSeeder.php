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

class PriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tours = Tour::all();

        foreach ($tours as $tour) {
            Price::factory(3)->for($tour, 'priceable')->create();
        }
    }
}
