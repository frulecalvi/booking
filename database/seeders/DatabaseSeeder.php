<?php

namespace Database\Seeders;

use App\Models\Schedule;
use App\Models\Tour;
use App\Models\User;
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

        $tours = Tour::factory(5)->create(['end_date' => '2026-07-01']);

        foreach ($tours as $tour) {
            Schedule::factory(10)->for($tour, 'scheduleable')->create();
        }

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
