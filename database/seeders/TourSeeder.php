<?php

namespace Database\Seeders;

use App\Models\Tour;
use App\Models\TourCategory;
use App\States\Tour\Active as TourActive;
use App\States\Tour\Inactive as TourInactive;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TourSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tourCategories = TourCategory::all();

        foreach ($tourCategories as $tourCategory) {
            $createdTours[TourActive::$name][] = Tour::factory(2)
                ->for($tourCategory)
                ->create(['state' => TourActive::$name, 'end_date' => now()->addYear()]);
            $createdTours[TourInactive::$name][] = Tour::factory(2)
                ->for($tourCategory)
                ->create(['state' => TourInactive::$name, 'end_date' => now()->addYear()]);
        }
    }
}
