<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\Tour;
use App\Models\TourCategory;
use App\Models\User;
use App\States\Schedule\Active as ScheduleActive;
use App\States\Tour\Active;
use App\States\Tour\Inactive;
use DateInterval;
use DateTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Str;

class TourTest extends TestCase
{
    use RefreshDatabase;

    protected $tours;
    protected $resourceType;
    protected $unsupportedFields;
    protected $requiredFields;
    protected $correctAttributes;
    protected $superAdminUser;
    protected $adminUser;
    protected $operatorUser;

    public function setUp(): void
    {
        parent::setUp();

        $this->resourceType = 'tours';

        $this->requiredFields = [
            ''
        ];

        $this->unsupportedFields = [
            'created_at' => 'algo',
            'updated_at' => 'algo',
            'deleted_at' => 'algo',
        ];

        $this->tours = [];

        foreach (Tour::getStatesFor('state')->toArray() as $state) {
            $this->tours[$state] = [
                Tour::factory()->make(['state' => $state]),
                Tour::factory()->make(['state' => $state]),
                Tour::factory()->make(['state' => $state])
            ];
        }

        $this->correctAttributes = [
            'name' => $this->tours[Active::$name][0]->name,
            'description' => $this->tours[Active::$name][0]->description,
            'duration' => $this->tours[Active::$name][0]->duration,
            'meetingPoint' => $this->tours[Active::$name][0]->meeting_point,
            'capacity' => $this->tours[Active::$name][0]->capacity,
            'endDate' => $this->tours[Active::$name][0]->end_date,
            'state' => Inactive::$name
        ];

        $this->superAdminUser = User::factory()->create();
        $this->superAdminUser->assignRole('Super Admin');
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('Admin');
        $this->operatorUser = User::factory()->create();
        $this->operatorUser->assignRole('Operator');
    }

    public function test_fetching_tours_rejects_invalid_accept_header()
    {
        // $this->withoutExceptionHandling();

        foreach ($this->tours as $tours) {
            foreach ($tours as $tour)
                $tour->save();
        }
        
        $response = $this
            ->get(route('v1.tours.index'), ['Accept' => 'application/json']);

            // dd($response);

        $response->assertNotAcceptable();
    }

    public function test_anonymous_users_can_fetch_only_active_resources()
    {
        $this->withoutExceptionHandling();

        foreach ($this->tours as $state => $tours) {
            foreach ($tours as $tour)
                $tour->save();
        }
        
        $response = $this
            ->jsonApi()
            ->expects('tours')
            ->get(route('v1.tours.index'));

        $response->assertFetchedMany($this->tours[Active::$name]);
    }

    public function test_operator_users_can_fetch_only_active_resources()
    {
        $this->withoutExceptionHandling();

        foreach ($this->tours as $tours) {
            foreach ($tours as $tour)
                $tour->save();
        }
        
        $response = $this
            ->actingAs($this->operatorUser)
            ->jsonApi()
            ->expects('tours')
            ->get(route('v1.tours.index'));

        $response->assertFetchedMany($this->tours[Active::$name]);
    }

    public function test_admin_users_can_fetch_only_resources_with_any_state()
    {
        // $this->withoutExceptionHandling();

        $createdTours = [];

        foreach ($this->tours as $tours) {
            array_push($createdTours, ...$tours);

            foreach ($tours as $tour)
                $tour->save();
        }
        
        $response = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects('tours')
            ->get(route('v1.tours.index'));

        $response->assertFetchedMany($createdTours);
    }

    public function test_fetching_tours_filtering_by_category_id_is_allowed_for_anonymous_users()
    {
        // $this->withoutExceptionHandling();

        $category = TourCategory::factory()->create();
        $tours = Tour::factory(4)
            ->for($category)
            ->create(['state' => Active::$name]);
        $tours2 = Tour::factory(3)
            ->create(['state' => Active::$name]);

        foreach ($tours as $tour) {
            $tour->save();
        }
        
        $response = $this
            ->jsonApi()
            ->expects('tours')
            ->filter(['tourCategoryId' => $category->id])
            ->get(route('v1.tours.index'));

        $response->assertFetchedMany($tours);
    }

    public function test_creating_tours_is_allowed_for_admin_users()
    {
        // $this->withoutExceptionHandling();

        $data = [
            'type' => $this->resourceType,
            'attributes' => $this->correctAttributes
        ];

        $adminResponse = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->includePaths(...$this->requiredFields)
            ->post(route('v1.tours.store'));

        $adminResponse->assertCreatedWithServerId(
            route('v1.tours.index'),
            $data
        );
    }

    public function test_creating_tours_is_forbidden_for_operator_users()
    {
        // $this->withoutExceptionHandling();

        $data = [
            'type' => $this->resourceType,
            'attributes' => $this->correctAttributes
        ];

        $operatorResponse = $this
            ->actingAs($this->operatorUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->includePaths(...$this->requiredFields)
            ->post(route('v1.tours.store'));

        $operatorResponse->assertErrorStatus(['status' => '403']);
    }

    public function test_creating_a_tour_is_forbidden_for_unauthenticated_users()
    {
        // $this->withoutExceptionHandling();

        $data = [
            'type' => $this->resourceType,
            'attributes' => $this->correctAttributes
        ];

        $operatorResponse = $this
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->includePaths(...$this->requiredFields)
            ->post(route('v1.tours.store'));

        $operatorResponse->assertErrorStatus(['status' => '401']);
    }

    public function test_creating_a_tour_rejects_filling_these_fields()
    {
        $data = [
            'type' => 'tours',
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
            ->expects('tours')
            ->withData($data)
            ->post(route('v1.tours.store'));

        $response->assertErrors(400, $expectedErrors);
    }

    public function test_creating_a_tour_accepts_filling_these_fields()
    {
        $this->withoutExceptionHandling();

        $data = [
            'type' => 'tours',
            'attributes' => $this->correctAttributes
        ];

        // dd($data);

        $response = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects('tours')
            ->withData($data)
            ->post(route('v1.tours.store'));

        $id = $response
            ->assertCreatedWithServerId(
                route('v1.tours.index'),
                $data
            )
            ->id();

        foreach ($this->correctAttributes as $field => $value) {
            $databaseHas[Str::snake($field)] = $value;
        }
        
        $this->assertDatabaseHas('tours', [
            'id' => $id,
            ...$databaseHas
        ]);
    }

    public function test_deleting_a_tour_is_forbidden_for_unauthenticated_users()
    {
        $tour = Tour::factory()->create(['state' => Active::$name]);

        $response = $this
            ->jsonApi()
            ->expects($this->resourceType)
            ->delete(route('v1.tours.destroy', $tour->getRouteKey()));
        
        $response->assertErrorStatus(['status' => '401']);
    }

    public function test_deleting_a_schedule_is_forbidden_for_opertator_users()
    {
        $tour = Tour::factory()->create(['state' => Active::$name]);

        $response = $this
            ->actingAs($this->operatorUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->delete(route('v1.tours.destroy', $tour->getRouteKey()));
        
        $response->assertErrorStatus(['status' => '403']);
    }

    public function test_deleting_a_schedule_is_allowed_for_admin_users()
    {
        // $this->withoutExceptionHandling();

        $tour = Tour::factory()->create();

        $response = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->delete(route('v1.tours.destroy', $tour->getRouteKey()));
        
        $response->assertNoContent();

        $this->assertSoftDeleted($tour);
    }

    public function test_updating_a_tour_is_forbidden_for_unauthenticated_users()
    {
        $tour = Tour::factory()->create(['state' => Active::$name]);

        $data = [
            'type' => $this->resourceType,
            'id' => $tour->getRouteKey(),
            'attributes' => [
                'state' => Inactive::$name
            ]
        ];

        $response = $this
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->patch(route('v1.tours.update', $tour->getRouteKey()));

        $response->assertErrorStatus(['status' => '401']);
    }

    public function test_updating_a_tour_is_forbidden_for_operator_users()
    {
        $tour = Tour::factory()->create(['state' => Active::$name]);

        $data = [
            'type' => $this->resourceType,
            'id' => $tour->getRouteKey(),
            'attributes' => [
                'state' => Inactive::$name
            ]
        ];

        $response = $this
            ->actingAs($this->operatorUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->patch(route('v1.tours.update', $tour->getRouteKey()));

        $response->assertErrorStatus(['status' => '403']);
    }

    public function test_updating_a_tour_is_allowed_for_admin_users()
    {
        $tour = Tour::factory()->create();

        $data = [
            'type' => $this->resourceType,
            'id' => $tour->getRouteKey(),
            'attributes' => [
                'state' => Inactive::$name
            ]
        ];

        $response = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->patch(route('v1.tours.update', $tour->getRouteKey()));

        $response->assertFetchedOne($data);
    }

    // public function test_fetching_a_tour_s_events_anonymously_includes_all_its_active_events()
    // {

    // }

    // public function test_fetching_a_tour_s_events_anonymously_returns_all_its_active_events()
    // {
        
    // }
}
