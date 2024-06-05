<?php

namespace Tests\Feature;

use App\Models\Tour;
use App\Models\User;
use App\States\Tour\Active;
use App\States\Tour\Inactive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use LaravelJsonApi\Testing\MakesJsonApiRequests;
use Illuminate\Support\Str;

class TourTest extends TestCase
{
    use MakesJsonApiRequests, RefreshDatabase;

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
            'meeting_point' => $this->tours[Active::$name][0]->meeting_point,
            'seating' => $this->tours[Active::$name][0]->seating,
            'state' => Inactive::$name
        ];

        $this->superAdminUser = User::factory()->create();
        $this->superAdminUser->assignRole('Super Admin');
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('Admin');
        $this->operatorUser = User::factory()->create();
        $this->operatorUser->assignRole('Operator');
    }

    public function test_anonymous_users_can_fetch_only_active_resources()
    {
        // $this->withoutExceptionHandling();

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

    public function test_creating_tours_is_allowed_for_admin_and_super_admin_users()
    {
        // $this->withoutExceptionHandling();

        $data = [
            'type' => $this->resourceType,
            'attributes' => $this->correctAttributes
        ];

        $superAdminResponse = $this
            ->actingAs($this->superAdminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->includePaths(...$this->requiredFields)
            ->post(route('v1.tours.store'));

        $superAdminResponse->assertCreated();

        $adminResponse = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->includePaths(...$this->requiredFields)
            ->post(route('v1.tours.store'));

        $adminResponse->assertCreated();
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

        $operatorResponse->assertForbidden();
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
            ->expects('bookings')
            ->withData($data)
            ->post(route('v1.tours.store'));

        $response->assertErrors(400, $expectedErrors);
    }

    public function test_creating_a_tour_accepts_filling_these_fields()
    {
        // $this->withoutExceptionHandling();

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
}
