<?php

namespace Database\Seeders;

use App\Models\Price;
use App\Models\Schedule;
use App\Models\Tour;
use App\Models\TourCategory;
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
            'email' => 'test@test.com',
            'password' => 'test'
        ]);

        $user->assignRole('Operator');

        $category1 = TourCategory::factory()->create();
        $category2 = TourCategory::factory()->create();
        $createdTours[TourActive::$name][] = Tour::factory()
            ->for($category1)
            ->create(['state' => TourActive::$name, 'end_date' => now()->addYear()]);
        $createdTours[TourActive::$name][] = Tour::factory()
            ->for($category1)
            ->create(['state' => TourActive::$name, 'end_date' => now()->addYear()]);
        $createdTours[TourActive::$name][] = Tour::factory()
            ->for($category2)
            ->create(['state' => TourActive::$name, 'end_date' => now()->addYear()]);
        $createdTours[TourActive::$name][] = Tour::factory()
            ->for($category2)
            ->create(['state' => TourActive::$name, 'end_date' => now()->addYear()]);
        $createdTours[TourActive::$name][] = Tour::factory()
            ->for($category2)
            ->create(['state' => TourActive::$name, 'end_date' => now()->addYear()]);
        $createdTours[TourInactive::$name][] = Tour::factory()
            ->for($category1)
            ->create(['state' => TourInactive::$name, 'end_date' => now()->addYear()]);
        $createdTours[TourInactive::$name][] = Tour::factory()
            ->for($category1)
            ->create(['state' => TourInactive::$name, 'end_date' => now()->addYear()]);
        $createdTours[TourInactive::$name][] = Tour::factory()
            ->for($category2)
            ->create(['state' => TourInactive::$name, 'end_date' => now()->addYear()]);
        $createdTours[TourInactive::$name][] = Tour::factory()
            ->for($category2)
            ->create(['state' => TourInactive::$name, 'end_date' => now()->addYear()]);

        foreach ($createdTours as $tours) {
            foreach ($tours as $tour) {
                Price::factory(3)->for($tour, 'priceable')->create();
                Schedule::factory(2)->for($tour, 'scheduleable')->create(['state' => ScheduleActive::$name, 'period' => 'weekly']);
            }
        }

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
