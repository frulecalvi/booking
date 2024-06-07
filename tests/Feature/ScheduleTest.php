<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Schedule;
use App\Models\Tour;
use App\Models\User;
use App\States\Schedule\Active;
use App\States\Schedule\Inactive;
use DateInterval;
use DateTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Str;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected $resourceType;
    protected $requiredFields;
    protected $readOnlyFields;
    protected $unsupportedFields;
    protected $acceptedPeriodValues;
    protected $correctAttributes;
    protected $correctRelationships;
    protected $tour;
    protected $schedules;
    protected $adminUser;
    protected $operatorUser;

    public function setUp(): void
    {
        parent::setUp();

        $this->resourceType = 'schedules';

        $this->requiredFields = [
            'attributes' => [
                'period',
                'day',
                'date',
                'time',
            ],
            'relationships' => ['product']
        ];

        $this->readOnlyFields = [
            'createdAt' => 'algo',
            'updatedAt' => 'algo',
            'deletedAt' => 'algo',
        ];

        $this->unsupportedFields = [
            'scheduleableType' => 'algo',
            'scheduleableId' => 'algo',
            'status' => 'algo'
        ];

        $this->acceptedPeriodValues = [
            'once',
            // 'daily',
            'weekly',
            // 'monthly',
        ];

        $this->tour = Tour::factory()->create();

        // dd(Schedule::getStatesFor('state')->toArray());

        $this->schedules = [];

        foreach (Schedule::getStatesFor('state')->toArray() as $state) {
            $this->schedules[$state] = [
                Schedule::factory()
                    ->for($this->tour, 'scheduleable')
                    ->make(['state' => $state]),
                Schedule::factory()
                    ->for($this->tour, 'scheduleable')
                    ->make(['state' => $state]),
                Schedule::factory()
                    ->for($this->tour, 'scheduleable')
                    ->make(['state' => $state]),
            ];
        }

        // dd($this->schedules);

        $this->correctAttributes = [
            'period' => $this->schedules[Active::$name][0]->period,
            'day' => $this->schedules[Active::$name][0]->day,
            'date' => $this->schedules[Active::$name][0]->date,
            // 'date' => $this->schedules[Active::$name][0]->date,
            'time' => $this->schedules[Active::$name][0]->time,
            // 'start_date' => $this->schedules[Active::$name][0]->start_date,
            'state' => Active::$name,
        ];

        // dd($this->correctAttributes);

        $this->correctRelationships = [
            'product' => [
                'data' => [
                    'type' => 'tours',
                    'id' => $this->tour->id
                ]
            ]
        ];

        $this->adminUser = User::factory()->create();
        $this->operatorUser = User::factory()->create();
        $this->adminUser->assignRole('Admin');
        $this->operatorUser->assignRole('Operator');
    }

    public function test_fetching_schedules_is_forbidden_for_unauthenticated_users()
    {
        // $this->withoutExceptionHandling();

        $response = $this
            ->jsonApi()
            ->expects($this->resourceType)
            ->get(route('v1.schedules.index'));

            // var_dump($response);
        
        $response->assertErrorStatus(['status' => '401']);
    }

    public function test_operator_users_can_fetch_only_active_resources()
    {
        // $this->withoutExceptionHandling();

        foreach ($this->schedules as $schedules) {
            foreach ($schedules as $schedule)
                $schedule->save();
        }

        $response = $this
            ->actingAs($this->operatorUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->get(route('v1.schedules.index'));

            // var_dump($response);
        
        $response->assertFetchedMany($this->schedules[Active::$name]);
    }

    public function test_admin_users_can_fetch_resources_with_any_state()
    {
        // $this->withoutExceptionHandling();

        $createdSchedules = [];

        foreach ($this->schedules as $schedules) {
            array_push($createdSchedules, ...$schedules);

            foreach ($schedules as $schedule)
                $schedule->save();
        }

        $response = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->get(route('v1.schedules.index'));

        // dd($response->getContent());
        
        $response->assertFetchedMany($createdSchedules);
    }

    public function test_fetching_a_single_schedule_is_forbidden_for_unauthenticated_users()
    {
        $this->schedules[Active::$name][0]->save();

        $response = $this
            ->jsonApi()
            ->expects($this->resourceType)
            ->get(route('v1.schedules.show', $this->schedules[Active::$name][0]->getRouteKey()));

        // dd($response->getContent());
        
        $response->assertErrorStatus(['status' => '401']);
    }

    public function test_creating_a_schedule_is_forbidden_for_unauthenticated_users()
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
            ->includePaths(...array_keys($this->correctRelationships))
            ->post(route('v1.schedules.store'));

        $response->assertErrorStatus(['status' => '401']);
    }

    public function test_creating_a_schedule_is_forbidden_for_operator_users()
    {
        // $this->withoutExceptionHandling();

        $data = [
            'type' => $this->resourceType,
            'attributes' => $this->correctAttributes,
            'relationships' => $this->correctRelationships
        ];

        // dd($data);

        $response = $this
            ->actingAs($this->operatorUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->includePaths(...array_keys($this->correctRelationships))
            ->post(route('v1.schedules.store'));

        $response->assertErrorStatus(['status' => '403']);
    }

    public function test_creating_a_schedule_is_allowd_for_admin_users()
    {
        // $this->withoutExceptionHandling();

        $data = [
            'type' => $this->resourceType,
            'attributes' => $this->correctAttributes,
            'relationships' => $this->correctRelationships
        ];

        // dd($data);

        $response = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->includePaths(...array_keys($this->correctRelationships))
            ->post(route('v1.schedules.store'));

        $response->assertCreatedWithServerId(
            route('v1.schedules.index'),
            $data
        );
    }

    public function test_creating_a_schedule_ignores_filling_these_fields()
    {
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

        sleep(1);

        $response = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($requestData)
            ->includePaths(...array_keys($this->correctRelationships))
            ->post(route('v1.schedules.store'));

        $id = $response
            ->assertCreatedWithServerId(
                route('v1.schedules.index'),
                $expectedData
            )
            ->id();


        $databaseHas = [];

        foreach ($this->correctAttributes as $field => $value) {
            $databaseHas[Str::snake($field)] = $value;
        }

        $this->assertDatabaseHas('schedules', [
            'id' => $id,
            ...$databaseHas
        ]);

        $createdSchedule = Schedule::findOrFail($id);

        foreach ($this->readOnlyFields as $field => $value) {
            $this->assertNotEquals($value, $createdSchedule->{Str::snake($field)});
        }
    }

    public function test_creating_a_schedule_rejects_filling_these_fields()
    {
        $data = [
            'type' => $this->resourceType,
            'attributes' => $this->unsupportedFields,
            'relationships' => $this->correctRelationships
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
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->post(route('v1.schedules.store'));

        // dd($response->getContent());

        $response->assertErrors(400, $expectedErrors);
    }

    public function test_creating_a_tour_accepts_filling_these_fields()
    {
        // $this->withoutExceptionHandling();

        $data = [
            'type' => $this->resourceType,
            'attributes' => $this->correctAttributes,
            'relationships' => $this->correctRelationships
        ];

        // dd($data);

        $response = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->includePaths(...array_keys($this->correctRelationships))
            ->post(route('v1.schedules.store'));

        $id = $response
            ->assertCreatedWithServerId(
                route('v1.schedules.index'),
                $data
            )
            ->id();

        foreach ($this->correctAttributes as $field => $value) {
            $databaseHas[Str::snake($field)] = $value;
        }
        
        $this->assertDatabaseHas($this->resourceType, [
            'id' => $id,
            ...$databaseHas
        ]);
    }

    public function test_creating_a_schedule_accepts_these_values_for_period_field()
    {
        foreach ($this->acceptedPeriodValues as $value) {
            $this->correctAttributes['period'] = $value;

            $data = [
                'type' => $this->resourceType,
                'attributes' => $this->correctAttributes,
                'relationships' => $this->correctRelationships
            ];

            // dd($data);
    
            $response = $this
                ->actingAs($this->adminUser)
                ->jsonApi()
                ->expects($this->resourceType)
                ->withData($data)
                ->includePaths(...array_keys($this->correctRelationships))
                ->post(route('v1.schedules.store'));
    
            $response
                ->assertCreatedWithServerId(
                    route('v1.schedules.index'),
                    $data
                )
                ->id();
        }
    }

    public function test_creating_a_schedule_fails_if_any_of_the_required_fields_is_omitted()
    {
        $data = [
            'type' => $this->resourceType,
            'attributes' => [
                $this->requiredFields['attributes'][0] => ''
            ]
        ];

        $dataOnce = [
            'type' => $this->resourceType,
            'attributes' => [
                $this->requiredFields['attributes'][0] => '',
                'period' => 'once'
            ]
        ];

        $expectedErrors = [];
        $expectedErrorsOnce = [];

        foreach ($this->requiredFields['attributes'] as $fieldName) {
            $snakeFieldName = Str::snake($fieldName, ' ');

            $detail = $fieldName === 'day' ?
                "The {$snakeFieldName} field is required unless period is in once."
                : (
                    $fieldName === 'date' ?
                        "The {$snakeFieldName} field is required when period is once."
                        : "The {$snakeFieldName} field is required."
                )
            ;

            if (! in_array($fieldName, ['date']))
                $expectedErrors[] = [
                    "detail" => $detail,
                    'source' => ['pointer' => "/data/attributes/{$fieldName}"],
                    'status' => '422',
                    "title" => "Unprocessable Entity"
                ];

            if (! in_array($fieldName, ['day', 'period']))
                $expectedErrorsOnce[] = [
                    "detail" => $detail,
                    'source' => ['pointer' => "/data/attributes/{$fieldName}"],
                    'status' => '422',
                    "title" => "Unprocessable Entity"
                ];
        }

        foreach ($this->requiredFields['relationships'] as $fieldName) {
            $snakeFieldName = Str::snake($fieldName, ' ');
            $errorRelationship = [
                "detail" => "The {$snakeFieldName} field is required.",
                'source' => ['pointer' => "/data/relationships/{$fieldName}"],
                'status' => '422',
                "title" => "Unprocessable Entity"
            ];

            $expectedErrors[] = $errorRelationship;
            $expectedErrorsOnce[] = $errorRelationship;
        }

        $response = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->post(route('v1.schedules.store'));

        $responseOnce = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($dataOnce)
            ->post(route('v1.schedules.store'));

        $response->assertErrors(422, $expectedErrors);
        $responseOnce->assertErrors(422, $expectedErrorsOnce);
    }

    // public function test_creating_a_schedule_is_possible_only_if_the_scheduleables_s_end_date_is_in_the_future()
    // {
    //     $data = [
    //         'type' => $this->resourceType,
    //         'attributes' => $this->correctAttributes,
    //         'relationships' => $this->correctRelationships
    //     ];

    //     $expectedError = [
    //         "detail" => "The resource's end date is not valid.",
    //         'source' => ['pointer' => "/data/relationships/product"],
    //         'status' => '422',
    //         "title" => "Unprocessable Entity"
    //     ];

    //     $response = $this
    //         ->actingAs($this->adminUser)
    //         ->jsonApi()
    //         ->expects($this->resourceType)
    //         ->withData($data)
    //         ->includePaths(...array_keys($this->correctRelationships))
    //         ->post(route('v1.schedules.store'));

    //     // dd($response);

    //     $response->assertError(422, $expectedError);
    // }

    public function test_creating_a_once_schedule_creates_its_associated_event()
    {
        $this->withoutExceptionHandling();

        $today = new DateTime();

        $futureDate = $today->add(DateInterval::createFromDateString('365 days'))->format('Y-m-d');

        $this->correctAttributes['period'] = 'once';
        $this->correctAttributes['date'] = $futureDate;

        $data = [
            'type' => $this->resourceType,
            'attributes' => $this->correctAttributes,
            'relationships' => $this->correctRelationships
        ];

        $response = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->includePaths(...array_keys($this->correctRelationships))
            ->post(route('v1.schedules.store'));

        $id = $response
            ->assertCreatedWithServerId(
                route('v1.schedules.index'),
                $data
            )->id();
        
        $this->assertDatabaseHas('events', [
            'schedule_id' => $id,
            'date' => $this->correctAttributes['date'],
            'time' => $this->correctAttributes['time'],
        ]);
    }

    public function test_creating_a_weekly_schedule_creates_all_its_associated_events()
    {
        $this->withoutExceptionHandling();
        
        $today = new DateTime();

        $futureDate = $today->add(DateInterval::createFromDateString('365 days'))->format('Y-m-d');

        $this->correctAttributes['period'] = 'weekly';
        // $this->correctAttributes['day'] = 5;
        // $this->correctAttributes['time'] = '12:00:00';
        $this->tour->end_date = $futureDate;
        $this->tour->save();

        $data = [
            'type' => $this->resourceType,
            'attributes' => $this->correctAttributes,
            'relationships' => $this->correctRelationships
        ];

        $response = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->includePaths(...array_keys($this->correctRelationships))
            ->post(route('v1.schedules.store'));

        $id = $response
            ->assertCreatedWithServerId(
                route('v1.schedules.index'),
                $data
            )->id();

        // var_dump($this->tour->date);
        // dd(getAllWeekdayDatesUntil($this->correctAttributes['day'], $this->tour->end_date));
        
        $expectedEventsDates = getAllWeekdayDatesUntil($this->correctAttributes['day'], $this->tour->end_date);

        foreach ($expectedEventsDates as $date) {
            $this->assertDatabaseHas('events', [
                'schedule_id' => $id,
                'date' => $date,
                'time' => $this->correctAttributes['time'],
            ]);
        }
    }

    public function test_deleting_a_schedule_is_forbidden_for_unauthenticated_users()
    {
        $createdSchedule = Schedule::factory()->for($this->tour, 'scheduleable')->create();

        $response = $this
            ->jsonApi()
            ->expects($this->resourceType)
            ->delete(route('v1.schedules.destroy', $createdSchedule->getRouteKey()));
        
        $response->assertErrorStatus(['status' => '401']);
    }

    public function test_deleting_a_schedule_is_forbidden_for_opertator_users()
    {
        $createdSchedule = Schedule::factory()->for($this->tour, 'scheduleable')->create();

        $response = $this
            ->actingAs($this->operatorUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->delete(route('v1.schedules.destroy', $createdSchedule->getRouteKey()));
        
        $response->assertErrorStatus(['status' => '403']);
    }

    public function test_deleting_a_schedule_is_allowed_for_admin_users()
    {
        $this->withoutExceptionHandling();

        $schedule = Schedule::factory()->for($this->tour, 'scheduleable')->create();

        $response = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->delete(route('v1.schedules.destroy', $schedule->getRouteKey()));
        
        $response->assertNoContent();

        $this->assertSoftDeleted($schedule);
    }

    public function test_deleting_a_once_schedule_deletes_its_associated_event()
    {
        $this->withoutExceptionHandling();

        $schedule = Schedule::factory()->for($this->tour, 'scheduleable')->create(['period' => 'once']);
        $events = $schedule->events;

        $expectedMissingEvents = [];

        foreach ($events as $event) {
            $expectedMissingEvents[] = ['id' => $event->id];
        }

        $response = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->delete(route('v1.schedules.destroy', $schedule->getRouteKey()));

        $response->assertNoContent();

        // dd($expectedMissingEvents);

        foreach ($events as $event) {
            $this->assertSoftDeleted($event);
        }
    }

    public function test_deleting_a_weekly_schedule_deletes_all_its_associated_events()
    {
        $this->withoutExceptionHandling();

        $today = new DateTime();
        $futureDate = $today->add(DateInterval::createFromDateString('365 days'))->format('Y-m-d');

        $this->tour->end_date = $futureDate;
        $this->tour->save();

        $schedule = Schedule::factory()->for($this->tour, 'scheduleable')->create(['period' => 'weekly']);
        $events = $schedule->events;

        $response = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->delete(route('v1.schedules.destroy', $schedule->getRouteKey()));

        $response->assertNoContent();

        // dd($expectedMissingEvents);

        foreach ($events as $event) {
            $this->assertSoftDeleted($event);
        }
    }

    public function test_updating_a_schedule_is_forbidden_for_unauthenticated_users()
    {
        $schedule = Schedule::factory()->for($this->tour, 'scheduleable')->create();

        $data = [
            'type' => $this->resourceType,
            'id' => $schedule->getRouteKey(),
            'attributes' => [
                'state' => Inactive::$name
            ]
        ];

        $response = $this
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->patch(route('v1.schedules.update', $schedule->getRouteKey()));

        $response->assertErrorStatus(['status' => '401']);
    }

    public function test_updating_a_schedule_is_forbidden_for_operator_users()
    {
        $schedule = Schedule::factory()->for($this->tour, 'scheduleable')->create();

        $data = [
            'type' => $this->resourceType,
            'id' => $schedule->getRouteKey(),
            'attributes' => [
                'state' => Inactive::$name
            ]
        ];

        $response = $this
            ->actingAs($this->operatorUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->patch(route('v1.schedules.update', $schedule->getRouteKey()));

        $response->assertErrorStatus(['status' => '403']);
    }

    public function test_updating_a_schedule_is_allowed_for_admin_users()
    {
        $schedule = Schedule::factory()->for($this->tour, 'scheduleable')->create();

        $data = [
            'type' => $this->resourceType,
            'id' => $schedule->getRouteKey(),
            'attributes' => [
                'state' => Inactive::$name
            ]
        ];

        $response = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->patch(route('v1.schedules.update', $schedule->getRouteKey()));

        $response->assertFetchedOne($data);
    }
}
