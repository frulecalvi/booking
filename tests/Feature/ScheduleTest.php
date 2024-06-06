<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\Tour;
use App\Models\User;
use App\States\Schedule\Active;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Str;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected $resourceType;
    protected $requiredFields;
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
                'time',
                'endDate',
            ],
            'relationships' => ['product']
        ];

        $this->unsupportedFields = [
            'created_at' => 'algo',
            'updated_at' => 'algo',
            'deleted_at' => 'algo',
        ];

        $this->acceptedPeriodValues = [
            'once',
            'daily',
            'weekly',
            'monthly',
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
            // 'date' => $this->schedules[Active::$name][0]->date,
            'time' => $this->schedules[Active::$name][0]->time,
            // 'start_date' => $this->schedules[Active::$name][0]->start_date,
            'endDate' => $this->schedules[Active::$name][0]->end_date,
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
            ->expects('schedules')
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
            ->expects('schedules')
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
            ->expects('schedules')
            ->get(route('v1.schedules.index'));

        // var_dump($createdSchedules);
        
        $response->assertFetchedMany($createdSchedules);
    }

    public function test_creating_a_schedule_rejects_filling_these_fields()
    {
        $data = [
            'type' => 'schedules',
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
            ->expects('schedules')
            ->withData($data)
            ->post(route('v1.schedules.store'));

        $response->assertErrors(400, $expectedErrors);
    }

    public function test_creating_a_tour_accepts_filling_these_fields()
    {
        // $this->withoutExceptionHandling();

        $data = [
            'type' => 'schedules',
            'attributes' => $this->correctAttributes,
            'relationships' => $this->correctRelationships
        ];

        // dd($data);

        $response = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects('schedules')
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
        
        $this->assertDatabaseHas('schedules', [
            'id' => $id,
            ...$databaseHas
        ]);
    }

    public function test_creating_a_schedule_accepts_these_values_for_period_field()
    {
        foreach ($this->acceptedPeriodValues as $value) {
            $this->correctAttributes['period'] = $value;

            $data = [
                'type' => 'schedules',
                'attributes' => $this->correctAttributes,
                'relationships' => $this->correctRelationships
            ];

            // dd($data);
    
            $response = $this
                ->actingAs($this->adminUser)
                ->jsonApi()
                ->expects('schedules')
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
            'type' => 'schedules',
            'attributes' => [
                $this->requiredFields['attributes'][0] => ''
            ]
        ];

        $dataOnce = [
            'type' => 'schedules',
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
                : "The {$snakeFieldName} field is required."
            ;

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
            ->expects('schedules')
            ->withData($data)
            ->post(route('v1.schedules.store'));

        $responseOnce = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects('schedules')
            ->withData($dataOnce)
            ->post(route('v1.schedules.store'));

        $response->assertErrors(422, $expectedErrors);
        $responseOnce->assertErrors(422, $expectedErrorsOnce);
    }

    public function test_creating_a_schedule_creates_all_of_its_associated_events()
    {

    }
}
