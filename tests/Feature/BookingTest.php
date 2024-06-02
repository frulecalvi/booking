<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Schedule;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Str;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_user_can_create_a_booking_for_an_event()
    {
        // $this->withExceptionHandling();
        
        $tour = Tour::factory()->create();

        $schedule = Schedule::factory()
            ->for($tour, 'scheduleable')
            ->create();

        $event = Event::factory()
            ->for($schedule)
            ->create();
        
        $booking = Booking::factory()
            ->for($event)
            ->make();

        // dd($booking->reference_code);

        $data = [
            'type' => 'bookings',
            'attributes' => [
                'referenceCode' => $booking->reference_code,
                // 'eventId' => $booking->event->id,
                // 'scheduleableId' => $booking->scheduleable->id,
                // 'contactName' => $booking->contact_name,
                // 'contactEmail' => $booking->contact_email,
                // 'status' => $booking->status
            ],
            // 'related' => [
            //     'event' => [
            //         'type' => 'events',
            //         'links' => [
            //             'self' => route('v1.events.show', $booking->event->id)
            //         ]
            //     ]
            // ]
        ];

        // dd($data);

        $response = $this
            ->jsonApi()
            ->expects('bookings')
            ->withData($data)
            ->post(route('v1.bookings.store'));

        $response
            ->assertCreatedWithServerId(
                route('v1.bookings.index'),
                $data
            );
    }
}
