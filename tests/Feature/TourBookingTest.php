<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Tour;
use App\States\Event\Active as EventActive;
use App\States\Tour\Active as TourActive;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TourBookingTest extends TestCase
{
    use RefreshDatabase;

    protected $resourceType;
    
    protected $tour;
    protected $event;
    protected $booking;

    protected $correctAttributes;
    protected $correctRelationships;

    public function setUp(): void
    {
        parent::setUp();

        $this->resourceType = 'bookings';

        $this->tour = Tour::factory()->create([
            'end_date' => now()->addYear(),
            'state' => TourActive::$name,
        ]);

        $this->event = Event::factory()
            ->for($this->tour, 'eventable')
            ->create(['state' => EventActive::$name, 'date_time' => now()->addDays(15)]);

        $this->booking = Booking::factory()->make();

        $this->correctAttributes = [
//            'contactName' => $this->booking->contact_name,
            'contactEmail' => $this->booking->contact_email,
            'contactPhoneNumber' => $this->booking->contact_phone_number,
        ];

        $this->correctRelationships = [
            'product' => [
                'data' => [
                    'type' => 'tours',
                    'id' => $this->tour->id
                ]
            ],
            'event' => [
                'data' => [
                    'type' => 'events',
                    'id' => $this->event->id
                ]
            ]
        ];
    }

    public function test_creating_tour_bookings_is_allowed_for_anonymous_users()
    {
        // $this->withoutExceptionHandling();

        $data = [
            'type' => $this->resourceType,
            'attributes' => $this->correctAttributes,
            'relationships' => $this->correctRelationships
        ];

        // dd($data);

        $response = $this
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->includePaths('product', 'event')
            ->post(route(('v1.bookings.store')));

            // dd($this->event);
        
        $id = $response->assertCreatedWithServerId(
            route('v1.bookings.index'),
            $data
        )->id();

        $this->assertDatabaseHas('bookings', ['id' => $id]);
    }
}
