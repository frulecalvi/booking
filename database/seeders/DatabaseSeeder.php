<?php

namespace Database\Seeders;

use App\Models\Schedule;
use App\Models\Tour;
use App\Models\User;
use App\States\Schedule\Active as ScheduleActive;
use App\States\Tour\Active as TourActive;
use App\States\Tour\Inactive as TourInactive;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'email' => 'test',
            'password' => 'test'
        ]);

        $user->assignRole('Operator');

        $createdTours[TourActive::$name] = Tour::factory(2)->create(['state' => TourActive::$name, 'end_date' => '2025-07-01']);
        $createdTours[TourInactive::$name] = Tour::factory(5)->create(['state' => TourInactive::$name, 'end_date' => '2025-07-01']);

        foreach ($createdTours as $tours) {
            foreach ($tours as $tour)
                Schedule::factory(2)->for($tour, 'scheduleable')->create(['state' => ScheduleActive::$name, 'period' => 'weekly']);
        }

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
