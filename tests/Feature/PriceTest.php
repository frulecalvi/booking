<?php

namespace Tests\Feature;

use App\Models\Price;
use App\Models\Tour;
use App\Models\User;
use App\States\Tour\Active as TourActive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PriceTest extends TestCase
{
    use RefreshDatabase;

    protected $resourceType;

    protected $adminUser;
    protected $operatorUser;

    protected $tour;
    protected $price;

    protected $requiredFields;
    protected $correctAttributes;
    protected $correctRelationships;

    public function setUp(): void
    {
        parent::setUp();

        $this->resourceType = 'prices';

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('Admin');
        $this->operatorUser = User::factory()->create();
        $this->operatorUser->assignRole('Operator');

        $this->tour = Tour::factory()->create(['end_date' => now()->addYear()]);
        $this->price = Price::factory()->make();

        $this->requiredFields = [
            'attributes' => [
                'title',
                'description',
                'amount',
                'currency',
            ],
            'relationships' => [
                'product'
            ]
        ];

        $this->correctAttributes = [
            'title' => $this->price->title,
            'description' => $this->price->description,
            'amount' => $this->price->amount,
            'currency' => $this->price->currency,
        ];

        $this->correctRelationships = [
            'product' => [
                'data' => [
                    'type' => 'tours',
                    'id' => $this->tour->id
                ]
            ]
        ];
    }

    public function test_creating_a_price_is_allowed_for_admin_users()
    {
        // $this->withoutExceptionHandling();

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
            ->includePaths('product')
            ->post(route('v1.prices.store'));

        $id = $response->assertCreatedWithServerId(
            route('v1.prices.index'),
            $data
        )->id();

        $this->assertDatabaseHas($this->resourceType, ['id' => $id]);
    }

    public function test_creating_a_price_is_forbidden_for_operator_users()
    {
        // $this->withoutExceptionHandling();

        $this->tour->state = TourActive::$name;
        $this->tour->save();

        $data = [
            'type' => $this->resourceType,
            'attributes' => $this->correctAttributes,
            'relationships' => $this->correctRelationships
        ];

        $response = $this
            ->actingAs($this->operatorUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->includePaths('product')
            ->post(route('v1.prices.store'));

        $response->assertErrorStatus(['status' => '403']);
    }

    public function test_creating_a_price_is_forbidden_for_unauthenticated_users()
    {
        // $this->withoutExceptionHandling();

        $this->tour->state = TourActive::$name;
        $this->tour->save();

        $data = [
            'type' => $this->resourceType,
            'attributes' => $this->correctAttributes,
            'relationships' => $this->correctRelationships
        ];

        $response = $this
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->includePaths('product')
            ->post(route('v1.prices.store'));

            $response->assertErrorStatus(['status' => '401']);
    }

    public function test_updating_a_price_is_allowed_for_admin_users()
    {
        // $this->withoutExceptionHandling();
        $this->tour->prices()->save($this->price);
        $newAmount = '25.23';

        $data = [
            'type' => $this->resourceType,
            'id' => $this->price->id,
            'attributes' => [
                'amount' => $newAmount
            ]
        ];

        $response = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->includePaths('product')
            ->patch(route('v1.prices.update', $this->price->getRouteKey()));

        $response->assertFetchedOne($data)->id();

        $this->assertDatabaseHas($this->resourceType, ['id' => $this->price->id, 'amount' => $newAmount]);
    }

    public function test_updating_a_price_is_forbidden_for_operator_users()
    {
        // $this->withoutExceptionHandling();
        $this->tour->prices()->save($this->price);
        $newAmount = '25.23';

        $data = [
            'type' => $this->resourceType,
            'id' => $this->price->id,
            'attributes' => [
                'amount' => $newAmount
            ]
        ];

        $response = $this
            ->actingAs($this->operatorUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->includePaths('product')
            ->patch(route('v1.prices.update', $this->price->getRouteKey()));

        $response->assertErrorStatus(['status' => '403']);
    }

    public function test_updating_a_price_is_forbidden_for_unauthenticated_users()
    {
        // $this->withoutExceptionHandling();
        $this->tour->prices()->save($this->price);
        $newAmount = '25.23';

        $data = [
            'type' => $this->resourceType,
            'id' => $this->price->id,
            'attributes' => [
                'amount' => $newAmount
            ]
        ];

        $response = $this
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->includePaths('product')
            ->patch(route('v1.prices.update', $this->price->getRouteKey()));

        $response->assertErrorStatus(['status' => '401']);
    }

    public function test_deleting_a_price_is_allowed_for_admin_users()
    {
        // $this->withoutExceptionHandling();
        $this->tour->prices()->save($this->price);

        $data = [
            'type' => $this->resourceType,
            'id' => $this->price->id
        ];

        $response = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->includePaths('product')
            ->delete(route('v1.prices.destroy', $this->price->getRouteKey()));

        $response->assertNoContent();

        $this->assertDatabaseMissing($this->resourceType, ['id' => $this->price->id]);
    }

    public function test_deleting_a_price_is_forbidden_for_operator_users()
    {
        // $this->withoutExceptionHandling();
        $this->tour->prices()->save($this->price);

        $data = [
            'type' => $this->resourceType,
            'id' => $this->price->id
        ];

        $response = $this
            ->actingAs($this->operatorUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->includePaths('product')
            ->delete(route('v1.prices.destroy', $this->price->getRouteKey()));

            $response->assertErrorStatus(['status' => '403']);
    }

    public function test_deleting_a_price_is_forbidden_for_unauthenticated_users()
    {
        // $this->withoutExceptionHandling();
        $this->tour->prices()->save($this->price);

        $data = [
            'type' => $this->resourceType,
            'id' => $this->price->id
        ];

        $response = $this
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->includePaths('product')
            ->delete(route('v1.prices.destroy', $this->price->getRouteKey()));

            $response->assertErrorStatus(['status' => '401']);
    }
}
