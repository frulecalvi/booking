<?php

namespace Tests\Feature;

use App\Models\Tour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use LaravelJsonApi\Testing\MakesJsonApiRequests;

class TourTest extends TestCase
{
    use MakesJsonApiRequests, RefreshDatabase;

    protected $tours;
    protected $resourceType;
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

        $this->tours = [
            Tour::factory()->create(),
            Tour::factory()->create(),
            Tour::factory()->create()
        ];

        $this->correctAttributes = [
            'name' => $this->tours[0]->name,
            'description' => $this->tours[0]->description,
            'duration' => $this->tours[0]->duration,
            'meeting_point' => $this->tours[0]->meeting_point,
            'seating' => $this->tours[0]->seating
        ];

        $this->superAdminUser = User::factory()->create();
        $this->superAdminUser->assignRole('Super Admin');
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('Admin');
        $this->operatorUser = User::factory()->create();
        $this->operatorUser->assignRole('Operator');
    }

    public function test_active_tours_collection_can_be_fetched_by_anonymous_users()
    {
        // $this->withoutExceptionHandling();
        
        $response = $this
            ->jsonApi()
            ->expects('tours')
            ->get(route('v1.tours.index'));

        $response->assertFetchedMany($this->tours);
    }

    public function test_creating_tours_is_allowed_only_for_admin_and_super_admin_users()
    {
        // $this->withoutExceptionHandling();

        $data = [
            'type' => $this->resourceType,
            'attributes' => $this->correctAttributes
        ];

        $responseSuperAdmin = $this
            ->actingAs($this->superAdminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->includePaths(...$this->requiredFields)
            ->post(route('v1.tours.store'));

        $responseSuperAdmin->assertCreated();

        $responseAdmin = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->includePaths(...$this->requiredFields)
            ->post(route('v1.tours.store'));

        $responseAdmin->assertCreated();

        $responseOperator = $this
            ->actingAs($this->operatorUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->includePaths(...$this->requiredFields)
            ->post(route('v1.tours.store'));

        $responseOperator->assertForbidden();
    }
}
