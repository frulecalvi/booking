<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Schedule;
use App\Models\Tour;
use App\Models\User;
use App\States\Booking\Inactive;
use App\States\Schedule\Active as ScheduleActive;
use App\States\Tour\Active as TourActive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Str;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    protected $resourceType;
    protected $requiredFields;
    protected $readOnlyFields;
    protected $unsupportedFields;

    protected $tour;
    protected $schedule;
    protected $event;
    protected $booking;

    protected $tour2;
    protected $schedule2;

    protected $tour3;

    protected $operatorUser;
    protected $adminUser;
    protected $superAdminUser;
    
    protected $correctAttributes;
    protected $correctRelationships;
    protected $unrelatedResourcesRelationships;

    public function setUp(): void
    {
        parent::setUp();

        $this->resourceType = 'bookings';

        $this->requiredFields = [
            'attributes' => [
                'contactName',
                'contactEmail'
            ],
            'relationships' => [
                'event',
                'product'
            ]
        ];

        $this->readOnlyFields = [
            'referenceCode' => 'algo',
        ];

        $this->unsupportedFields = [
            'eventId' => 'algo',
            'eventDateTime' => 'algo',
            'bookingId' => 'algo',
            'bookingableType' => 'algo',
            'bookingableId' => 'algo',
            'bookingableDescription' => 'algo',
            'status' => 'algo'
        ];

        $this->tour = Tour::factory()->create(['state' => TourActive::$name, 'end_date' => now()->addYears(1)]);
        $this->schedule = Schedule::factory()->for($this->tour, 'scheduleable')->create(['state' => ScheduleActive::$name, 'date' => now()->addDays(15)]);
        $this->event = $this->tour->events->first();
        // dd([$this->tour->events, $this->schedule]);
        $this->booking = Booking::factory()
            ->for($this->event)
            ->for($this->schedule)
            ->for($this->tour, 'bookingable')
            ->make();

        $this->tour2 = Tour::factory()->create(['state' => TourActive::$name]);
        $this->schedule2 = Schedule::factory()->for($this->tour2, 'scheduleable')->create(['state' => ScheduleActive::$name]);

        $this->tour3 = Tour::factory()->create(['state' => TourActive::$name]);

        $this->operatorUser = User::factory()->create();
        $this->operatorUser->assignRole('Operator');

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('Admin');

        $this->superAdminUser = User::factory()->create();
        $this->superAdminUser->assignRole('Super Admin');
        
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
            'product' => [
                'data' => [
                    'type' => 'tours',
                    'id' => $this->tour3->id
                ]
            ]
        ];
    }

    public function test_fetching_bookings_rejects_invalid_accept_header()
    {
        // $this->withoutExceptionHandling();

        $this->booking->save();
        
        $response = $this
            ->get(route('v1.bookings.index'), ['Accept' => 'application/json']);

            // dd($response);

        $response->assertNotAcceptable();
    }

    public function test_anonymous_user_can_create_a_booking_for_an_event()
    {
        $this->withoutExceptionHandling();

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
            ->includePaths(...array_keys($this->correctRelationships))
            ->post(route('v1.bookings.store'));

        $id = $response
            ->assertCreatedWithServerId(
                route('v1.bookings.index'),
                $data
            )
            ->id();
        
        $this->assertDatabaseHas($this->resourceType, ['id' => $id]);
    }

    public function test_creating_a_booking_rejects_filling_these_fields()
    {
        $data = [
            'type' => $this->resourceType,
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
            ->expects($this->resourceType)
            ->withData($data)
            ->post(route('v1.bookings.store'));

        $response->assertErrors(400, $expectedErrors);
    }

    public function test_creating_a_booking_accepts_filling_these_fields()
    {
        $data = [
            'type' => $this->resourceType,
            'attributes' => $this->correctAttributes,
            'relationships' => $this->correctRelationships
        ];

        $response = $this
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->includePaths(...array_keys($this->correctRelationships))
            ->post(route('v1.bookings.store'));

        $id = $response
            ->assertCreatedWithServerId(
                route('v1.bookings.index'),
                $data
            )
            ->id();
        
        $this->assertDatabaseHas($this->resourceType, [
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
            'type' => $this->resourceType,
            'attributes' => $this->correctAttributes,
            'relationships' => $this->correctRelationships
        ];

        $requestData = [
            'type' => $this->resourceType,
            'attributes' => $this->correctAttributes + $this->readOnlyFields,
            'relationships' => $this->correctRelationships
        ];

        // dd($requestData);

        sleep(1);

        $response = $this
            ->jsonApi()
            ->expects($this->resourceType)
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

        foreach ($this->correctAttributes as $field => $value) {
            $databaseHas[Str::snake($field)] = $value;
        }

        $this->assertDatabaseHas($this->resourceType, [
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
            'type' => $this->resourceType,
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
            ->expects($this->resourceType)
            ->withData($data)
            ->post(route('v1.bookings.store'));

            // dd($response);

        $response->assertErrors(422, $expectedErrors);
    }

    public function test_creating_a_booking_correctly_saves_parent_relationships_data()
    {
        $data = [
            'type' => $this->resourceType,
            'attributes' => $this->correctAttributes,
            'relationships' => $this->correctRelationships
        ];

        $response = $this
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->includePaths(...array_keys($this->correctRelationships))
            ->post(route('v1.bookings.store'));

        $id = $response
            ->assertCreatedWithServerId(
                route('v1.bookings.index'),
                $data
            )
            ->id();
        
        $this->assertDatabaseHas($this->resourceType, [
            'id' => $id,
            'event_id' => $this->event->id,
            'event_date_time' => $this->event->date_time,
            'schedule_id' => $this->schedule->id,
            'bookingable_type' => get_class($this->tour),
            'bookingable_id' => $this->tour->id,
            'bookingable_description' => $this->tour->description
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
            'type' => $this->resourceType,
            'attributes' => $this->correctAttributes,
            'relationships' => $wrongRelationships
        ];

        $response = $this
            ->jsonApi()
            ->expects($this->resourceType)
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
            ->expects($this->resourceType)
            ->withData($data)
            ->post(route('v1.bookings.store'));

        $response->assertErrors(404, $expectedErrors);
    }

    public function test_creating_a_booking_fails_if_any_of_the_resources_is_not_related_to_the_others()
    {
        $data = [
            'type' => $this->resourceType,
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
            ->expects($this->resourceType)
            ->withData($data)
            ->includePaths(...array_keys($this->correctRelationships))
            ->post(route('v1.bookings.store'));

        // dd($response);

        $response->assertErrors(422, $expectedErrors);
    }

    public function test_fetching_bookings_is_forbidden_for_unauthenticated_users()
    {
        $response = $this
            ->jsonApi()
            ->expects($this->resourceType)
            ->get(route('v1.bookings.index'));

        // dd($response->getContent());
        
        $response->assertErrorStatus(['status' => '401']);
    }

    public function test_fetching_bookings_is_allowed_for_operator_users()
    {
        $this->booking->save();

        $response = $this
            ->actingAs($this->operatorUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->get(route('v1.bookings.index'));

        // dd($response->getContent());
        
        $response->assertFetchedMany([$this->booking]);
    }

    public function test_fetching_bookings_is_allowed_for_admin_users()
    {
        $this->booking->save();

        $response = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->get(route('v1.bookings.index'));

        // dd($response->getContent());
        
        $response->assertFetchedMany([$this->booking]);
    }

    public function test_fetching_a_single_booking_is_forbidden_for_unauthenticated_users()
    {
        $this->booking->save();

        $response = $this
            ->jsonApi()
            ->expects($this->resourceType)
            ->get(route('v1.bookings.show', $this->booking));

        // dd($response->getContent());
        
        $response->assertErrorStatus(['status' => '401']);
    }

    public function test_fetching_a_single_booking_is_allowed_for_operator_users()
    {
        $this->booking->save();

        $response = $this
            ->actingAs($this->operatorUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->get(route('v1.bookings.show', $this->booking->getRouteKey()));

        // dd($response->getContent());
        
        $response->assertFetchedOne($this->booking);
    }

    public function test_fetching_a_single_booking_is_allowed_for_admin_users()
    {
        $this->booking->save();

        $response = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->get(route('v1.bookings.show', $this->booking->getRouteKey()));

        // dd($response->getContent());
        
        $response->assertFetchedOne($this->booking);
    }

    public function test_deleting_a_booking_is_forbidden_for_unauthenticated_users()
    {
        $this->booking->save();

        $response = $this
            ->jsonApi()
            ->expects($this->resourceType)
            ->delete(route('v1.bookings.destroy', $this->booking->getRouteKey()));
        
        $response->assertErrorStatus(['status' => '401']);
    }

    public function test_deleting_a_booking_is_forbidden_for_opertator_users()
    {
        $this->booking->save();

        $response = $this
            ->actingAs($this->operatorUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->delete(route('v1.bookings.destroy', $this->booking->getRouteKey()));
        
        $response->assertErrorStatus(['status' => '403']);
    }

    public function test_deleting_a_booking_is_forbidden_for_admin_users()
    {
        // $this->withoutExceptionHandling();

        $this->booking->save();

        $response = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->delete(route('v1.bookings.destroy', $this->booking->getRouteKey()));
        
        $response->assertErrorStatus(['status' => '403']);
    }

    public function test_deleting_a_booking_is_allowed_for_super_admin_users()
    {
        // $this->withoutExceptionHandling();

        $this->booking->save();

        $response = $this
            ->actingAs($this->superAdminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->delete(route('v1.bookings.destroy', $this->booking->getRouteKey()));
        
        $response->assertNoContent();

        $this->assertSoftDeleted($this->booking);
    }

    public function test_updating_a_booking_is_forbidden_for_unauthenticated_users()
    {
        $this->booking->save();

        $data = [
            'type' => $this->resourceType,
            'id' => $this->booking->getRouteKey(),
            'attributes' => [
                'state' => Inactive::$name
            ]
        ];

        $response = $this
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->patch(route('v1.bookings.update', $this->booking->getRouteKey()));

        $response->assertErrorStatus(['status' => '401']);
    }

    public function test_updating_a_booking_is_allowed_for_operator_users()
    {
        $this->withoutExceptionHandling();
        $this->booking->save();

        $data = [
            'type' => $this->resourceType,
            'id' => $this->booking->getRouteKey(),
            'attributes' => [
                'state' => Inactive::$name
            ]
        ];

        $response = $this
            ->actingAs($this->operatorUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->patch(route('v1.bookings.update', $this->booking->getRouteKey()));

        $response->assertFetchedOne($data);
    }

    public function test_updating_a_booking_is_allowed_for_admin_users()
    {
        $this->booking->save();

        $data = [
            'type' => $this->resourceType,
            'id' => $this->booking->getRouteKey(),
            'attributes' => [
                'state' => Inactive::$name
            ]
        ];

        $response = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->patch(route('v1.bookings.update', $this->booking->getRouteKey()));

        $response->assertFetchedOne($data);
    }
}
