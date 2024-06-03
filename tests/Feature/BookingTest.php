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

    protected $requiredFields;
    protected $acceptedFields;
    protected $readOnlyFields;
    protected $unsupportedFields;

    protected $tour;
    protected $schedule;
    protected $event;
    protected $booking;
    
    protected $correctAttributes;
    protected $correctRelationships;

    public function setUp(): void
    {
        parent::setUp();

        $this->requiredFields = [
            'event'
        ];

        $this->acceptedFields = [
            'contactName' => 'algo',
            'contactEmail' => 'un@email.com'
        ];

        $this->readOnlyFields = [
            'referenceCode' => 'algo',
            'createdAt' => now(),
            'updatedAt' => now(),
            'deletedAt' => now()
        ];

        $this->unsupportedFields = [
            'eventId' => 'algo',
            'eventDate' => 'algo',
            'eventTime' => 'algo',
            'scheduleId' => 'algo',
            'scheduleableId' => 'algo',
            'scheduleableDescription' => 'algo',
            'status' => 'algo'
        ];

        $this->tour = Tour::factory()->create();
        $this->schedule = Schedule::factory()->for($this->tour, 'scheduleable')->create();
        $this->event = Event::factory()->for($this->schedule)->create();
        $this->booking = Booking::factory()->for($this->event)->make();
        
        $this->correctAttributes = [
            'contactName' => $this->booking->contact_name,
            'contactEmail' => $this->booking->contact_email
        ];

        $this->correctRelationships = [
            'event' => [
                'data' => [
                    'type' => 'events',
                    'id' => $this->booking->event->id
                ]
            ]
        ];
    }

    public function test_anonymous_user_can_create_a_booking_for_an_event()
    {
        $data = [
            'type' => 'bookings',
            'attributes' => $this->correctAttributes,
            'relationships' => $this->correctRelationships
        ];

        $response = $this
            ->jsonApi()
            ->expects('bookings')
            ->withData($data)
            ->includePaths('event')
            ->post(route('v1.bookings.store'));

        $id = $response
            ->assertCreatedWithServerId(
                route('v1.bookings.index'),
                $data
            )
            ->id();
        
        $this->assertDatabaseHas('bookings', ['id' => $id]);
    }

    public function test_creating_a_booking_rejects_filling_these_fields()
    {
        $data = [
            'type' => 'bookings',
            'attributes' => $this->unsupportedFields
        ];

        $expectedErrors = [];

        foreach ($this->unsupportedFields as $field => $value) {
            $expectedErrors[] = [
                "detail" => "The field {$field} is not a supported attribute.",
                'source' => ['pointer' => '/data/attributes'],
                'status' => '400',
                "title" => "Non-Compliant JSON:API Document"
            ];
        }

        $response = $this
            ->jsonApi()
            ->expects('bookings')
            ->withData($data)
            ->post(route('v1.bookings.store'));

        $response->assertErrors(400, $expectedErrors);
    }

    public function test_creating_a_booking_accepts_filling_these_fields()
    {
        $data = [
            'type' => 'bookings',
            'attributes' => $this->correctAttributes,
            'relationships' => $this->correctRelationships
        ];

        $response = $this
            ->jsonApi()
            ->expects('bookings')
            ->withData($data)
            ->includePaths('event')
            ->post(route('v1.bookings.store'));

        $id = $response
            ->assertCreatedWithServerId(
                route('v1.bookings.index'),
                $data
            )
            ->id();
        
        $this->assertDatabaseHas('bookings', [
            'id' => $id,
            'contact_name' => $this->booking->contact_name,
            'contact_email' => $this->booking->contact_email,
            'event_id' => $this->booking->event->id,
        ]);
    }
    
    public function test_creating_a_booking_ignores_filling_these_fields()
    {
        // $this->withoutExceptionHandling();

        $expectedData = [
            'type' => 'bookings',
            'attributes' => $this->acceptedFields,
            'relationships' => $this->correctRelationships
        ];

        $requestData = [
            'type' => 'bookings',
            'attributes' => $this->acceptedFields + $this->readOnlyFields,
            'relationships' => $this->correctRelationships
        ];

        sleep(1);

        $response = $this
            ->jsonApi()
            ->expects('bookings')
            ->withData($requestData)
            ->includePaths('event')
            ->post(route('v1.bookings.store'));

        $id = $response
            ->assertCreatedWithServerId(
                route('v1.bookings.index'),
                $expectedData
            )
            ->id();

        $databaseHas = [];

        foreach ($this->acceptedFields as $field => $value) {
            $databaseHas[Str::snake($field)] = $value;
        }

        $this->assertDatabaseHas('bookings', [
            'id' => $id,
            ...$databaseHas
        ]);

        $createdBooking = Booking::findOrFail($id);

        foreach ($this->readOnlyFields as $field => $value) {
            $this->assertNotEquals($value, $createdBooking->{Str::snake($field)});
        }
    }

    public function test_creating_a_booking_requires_relationships_data()
    {
        $data = [
            'type' => 'bookings',
            'attributes' => $this->correctAttributes
        ];

        $expectedErrors = [];

        foreach ($this->requiredFields as $fieldName) {
            $expectedErrors[] = [
                "detail" => "The {$fieldName} field is required.",
                'source' => ['pointer' => '/data/relationships/event'],
                'status' => '422',
                "title" => "Unprocessable Entity"
            ];
        }

        $response = $this
            ->jsonApi()
            ->expects('bookings')
            ->withData($data)
            ->post(route('v1.bookings.store'));

        $response->assertErrors(422, $expectedErrors);
    }

    public function test_creating_a_booking_correctly_saves_parent_relationships_data()
    {
        $data = [
            'type' => 'bookings',
            'attributes' => $this->correctAttributes,
            'relationships' => $this->correctRelationships
        ];

        $response = $this
            ->jsonApi()
            ->expects('bookings')
            ->withData($data)
            ->includePaths('event')
            ->post(route('v1.bookings.store'));

        $id = $response
            ->assertCreatedWithServerId(
                route('v1.bookings.index'),
                $data
            )
            ->id();
        
        $this->assertDatabaseHas('bookings', [
            'id' => $id,
            'event_id' => $this->event->id,
            'event_date' => $this->event->date,
            'event_time' => $this->event->time,
            'schedule_id' => $this->schedule->id,
            'scheduleable_id' => $this->tour->id,
            'scheduleable_description' => $this->tour->description
        ]);
    }
}
