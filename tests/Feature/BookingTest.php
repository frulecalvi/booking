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

    protected $tour2;
    protected $schedule2;

    protected $tour3;
    
    protected $correctAttributes;
    protected $correctRelationships;
    protected $unrelatedResourcesRelationships;

    public function setUp(): void
    {
        parent::setUp();

        $this->requiredFields = [
            'attributes' => [
                'contactName',
                'contactEmail'
            ],
            'relationships' => [
                'event',
                'schedule',
                'product'
            ]
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

        $this->tour2 = Tour::factory()->create();
        $this->schedule2 = Schedule::factory()->for($this->tour2, 'scheduleable')->create();

        $this->tour3 = Tour::factory()->create();
        
        $this->correctAttributes = [
            'contactName' => $this->booking->contact_name,
            'contactEmail' => $this->booking->contact_email
        ];

        $this->correctRelationships = [
            'event' => [
                'data' => [
                    'type' => 'events',
                    'id' => $this->event->id
                ]
            ],
            'schedule' => [
                'data' => [
                    'type' => 'schedules',
                    'id' => $this->schedule->id
                ]
            ],
            'product' => [
                'data' => [
                    'type' => 'tours',
                    'id' => $this->tour->id
                ]
            ]
        ];

        $this->unrelatedResourcesRelationships = [
            'event' => [
                'data' => [
                    'type' => 'events',
                    'id' => $this->event->id
                ]
            ],
            'schedule' => [
                'data' => [
                    'type' => 'schedules',
                    'id' => $this->schedule2->id
                ]
            ],
            'product' => [
                'data' => [
                    'type' => 'tours',
                    'id' => $this->tour3->id
                ]
            ]
        ];
    }

    public function test_anonymous_user_can_create_a_booking_for_an_event()
    {
        // $this->withoutExceptionHandling();

        $data = [
            'type' => 'bookings',
            'attributes' => $this->correctAttributes,
            'relationships' => $this->correctRelationships
        ];

        // dd($data);

        $response = $this
            ->jsonApi()
            ->expects('bookings')
            ->withData($data)
            ->includePaths(...array_keys($this->correctRelationships))
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
            ->includePaths(...array_keys($this->correctRelationships))
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
            ->includePaths(...array_keys($this->correctRelationships))
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

    public function test_creating_a_booking_fails_if_any_required_field_is_omitted()
    {
        $data = [
            'type' => 'bookings',
            'attributes' => [
                $this->requiredFields['attributes'][0] => ''
            ]
        ];

        $expectedErrors = [];

        foreach ($this->requiredFields['attributes'] as $fieldName) {
            $snakeFieldName = Str::snake($fieldName, ' ');
            $expectedErrors[] = [
                "detail" => "The {$snakeFieldName} field is required.",
                'source' => ['pointer' => "/data/attributes/{$fieldName}"],
                'status' => '422',
                "title" => "Unprocessable Entity"
            ];
        }

        foreach ($this->requiredFields['relationships'] as $fieldName) {
            $snakeFieldName = Str::snake($fieldName, ' ');
            $expectedErrors[] = [
                "detail" => "The {$snakeFieldName} field is required.",
                'source' => ['pointer' => "/data/relationships/{$fieldName}"],
                'status' => '422',
                "title" => "Unprocessable Entity"
            ];
        }

        $response = $this
            ->jsonApi()
            ->expects('bookings')
            ->withData($data)
            ->post(route('v1.bookings.store'));

            // dd($response);

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
            ->includePaths(...array_keys($this->correctRelationships))
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
            'scheduleable_type' => get_class($this->tour),
            'scheduleable_id' => $this->tour->id,
            'scheduleable_description' => $this->tour->description
        ]);
    }

    public function test_creating_a_booking_fails_if_any_of_the_related_resources_does_not_exist()
    {
        $wrongRelationships = array_map(
            function ($field) {
                $modifiedArray = $field;
                $modifiedArray['data']['id'] = 'inexistente';
                return $modifiedArray;
            },
            $this->correctRelationships
        );

        $data = [
            'type' => 'bookings',
            'attributes' => $this->correctAttributes,
            'relationships' => $wrongRelationships
        ];

        $response = $this
            ->jsonApi()
            ->expects('bookings')
            ->withData($data)
            ->includePaths(...array_keys($this->correctRelationships))
            ->post(route('v1.bookings.store'));

        foreach ($this->requiredFields['relationships'] as $fieldName) {
            $expectedErrors[] = [
                "detail" => "The related resource does not exist.",
                'source' => ['pointer' => "/data/relationships/{$fieldName}"],
                'status' => '404',
                "title" => "Not Found"
            ];
        }

        $response = $this
            ->jsonApi()
            ->expects('bookings')
            ->withData($data)
            ->post(route('v1.bookings.store'));

        $response->assertErrors(404, $expectedErrors);
    }

    public function test_creating_a_booking_fails_if_any_of_the_resources_is_not_related_to_the_others()
    {
        $data = [
            'type' => 'bookings',
            'attributes' => $this->correctAttributes,
            'relationships' => $this->unrelatedResourcesRelationships
        ];

        foreach ($this->requiredFields['relationships'] as $field) {
            if ($field !== 'event')
                $expectedErrors[] = [
                    "detail" => "The resource is not properly related.",
                    'source' => ['pointer' => "/data/relationships/{$field}"],
                    'status' => '422',
                    "title" => "Unprocessable Entity"
                ];
        }

        $response = $this
            ->jsonApi()
            ->expects('bookings')
            ->withData($data)
            ->includePaths(...array_keys($this->correctRelationships))
            ->post(route('v1.bookings.store'));

        // dd($response);

        $response->assertErrors(422, $expectedErrors);
    }
}
