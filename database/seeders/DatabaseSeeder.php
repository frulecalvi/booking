<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\PaymentMethod;
use App\Models\Price;
use App\Models\Schedule;
use App\Models\Ticket;
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
        $operatorUser = User::factory()->create([
            'email' => 'operatortest@test.com',
            'password' => 'test'
        ]);
        $operatorUser->assignRole('Operator');

        $adminUser = User::factory()->create([
            'email' => 'admintest@test.com',
            'password' => 'test'
        ]);
        $adminUser->assignRole('Admin');

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

        $createdPaymentMethods = PaymentMethod::factory(2)->create([
            'secrets' => [
                'access_token' => env('MP_TEST_ACCESS_TOKEN'),
                'webhook_secret' => 'secret',
            ]
        ]);

        foreach ($createdTours as $tours) {
            foreach ($tours as $tour) {
                $tour->paymentMethods()->saveMany($createdPaymentMethods);
                Price::factory(3)->for($tour, 'priceable')->create();
                Schedule::factory(2)->for($tour, 'scheduleable')->create(['state' => ScheduleActive::$name, 'period' => 'weekly']);

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

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
