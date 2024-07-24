<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Tour;
use App\States\Schedule\Active as ScheduleActive;
use App\States\Tour\Active as TourActive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MercadoPagoTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->tour = Tour::factory()->create(['state' => TourActive::$name]);
        $this->schedule = Schedule::factory()
            ->for($this->tour, 'scheduleable')
            ->create(['period' => 'once', 'state' => ScheduleActive::$name, 'date' => now()->addDays(2)]);

//        dd(Event::all());
        $this->event = $this->schedule->events->first();
        $this->booking = Booking::factory()
            ->for($this->tour, 'bookingable')
            ->for($this->schedule)
            ->for($this->event)
            ->create();

        $this->payment = Payment::factory()
            ->for($this->booking)
            ->make();

        $this->correctRelationships = [
            'booking' => [
                'data' => [
                    'type' => 'bookings',
                    'id' => $this->booking->id,
                ],
            ],
        ];
    }

//    public function test_mercadopago_create_preference_returns_meta_data_with_preference_id()
//    {
//
//    }

    public function test_mercadopago_webhook_endpoint_is_defined()
    {
        $this->payment->save();

        $response = $this->postJson(route('v1.payments.mpUpdate', $this->payment), []);

        $this->assertNotEquals(404, $response->status());
        $this->assertNotEquals(405, $response->status());
    }
}
