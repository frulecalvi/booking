<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Tour;
use App\States\Tour\Active as TourActive;
use App\States\Schedule\Active as ScheduleActive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public Tour $tour;
    public Event $event;
    public Schedule $schedule;
    public Booking $booking;
    public Payment $payment;

    public $resourceType = 'payments';

    public $correctRelationships;

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
    public function test_creating_a_payment_with_its_related_booking_is_allowed_for_anonymous_users(): void
    {
        $this->withoutExceptionHandling();

        $data = [
            'type' => $this->resourceType,
            'relationships' => $this->correctRelationships,
        ];

        $response = $this
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->includePaths('booking')
            ->post(route('v1.payments.store'));

        $id = $response->assertCreatedWithServerId(
            route('v1.payments.index'),
            $data
        )->id();

        $this->assertDatabaseHas('payments', ['id' => $id]);
    }
}
