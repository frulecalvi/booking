<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\PaymentMethod;
use App\Models\Price;
use App\Models\Schedule;
use App\Models\Ticket;
use App\Models\Tour;
use App\States\Schedule\Active as ScheduleActive;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $createdPaymentMethods = PaymentMethod::factory(2)->create([
            'secrets' => [
                'access_token' => env('MP_TEST_ACCESS_TOKEN'),
                'webhook_secret' => 'secret',
            ]
        ]);

        $tours = Tour::all();

        foreach ($tours as $tour) {
            $tour->paymentMethods()->saveMany($createdPaymentMethods);
        }
    }
}
