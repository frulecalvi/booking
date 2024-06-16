<?php

namespace Tests\Feature;

use App\Models\Tour;
use App\Models\TourCategory;
use App\States\Tour\Active as TourActive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TourCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected $resourceType;

    protected $category;
    protected $category2;
    protected $tours;
    protected $tours2;
    
    protected $correctAttributes;
    protected $correctRelationships;

    public function setUp(): void
    {
        parent::setUp();

        $this->resourceType = 'tour-categories';

        $this->category = TourCategory::factory()->create();
        $this->category2 = TourCategory::factory()->create();

        $this->tours = Tour::factory(3)->for($this->category)->create(['state' => TourActive::$name]);
        $this->tours2 = Tour::factory(4)->for($this->category2)->create(['state' => TourActive::$name]);

        $this->correctAttributes = [
            'name' => $this->category->name,
            'description' => $this->category->description,
        ];

        $this->correctRelationships = [
            'tours' =>  [
                'data' => array_map(fn($tour) => [
                    'type' => 'tours',
                    'id' => $tour['id'],
                ], $this->tours->toArray())
            ]
        ];
    }

    public function test_fetching_a_category_including_its_child_tours_is_allowed_for_unauthenticated_users()
    {
        $this->withoutExceptionHandling();

        $expected = [
            'id' => $this->category->id,
            'type' => $this->resourceType,
            'attributes' => $this->correctAttributes,
            'relationships' => $this->correctRelationships,
        ];

        // dd([$this->tours->first(), $this->category]);

        $response = $this
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($expected)
            ->includePaths(...array_keys($this->correctRelationships))
            ->get(route('v1.tour-categories.show', $this->category->getRouteKey()));
        
        $response->assertFetchedOne($expected)
            ->assertIncluded(array_map(fn($tour) => ['type' => 'tours', 'id' => $tour['id']], $this->tours->toArray()));
    }

    public function test_fetching_a_tour_caegories_is_allowed_for_unauthenticated_users()
    {
        // $this->withoutExceptionHandling();

        // $categories = TourCategory::factory(4)->create();

        // dd([$this->tours->first(), $this->category]);

        $response = $this
            ->jsonApi()
            ->expects($this->resourceType)
            ->get(route('v1.tour-categories.index'));
        
        $response->assertFetchedMany(TourCategory::all());
    }
}
