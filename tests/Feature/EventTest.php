<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Str;

class EventTest extends TestCase
{
    use RefreshDatabase;

    protected $resourceType;
    protected $tour;
    protected $event;
    
    protected $requiredFields;
    protected $acceptedFields;
    protected $correctAttributes;
    protected $correctRelationships;

    protected $adminUser;
    protected $operatorUser;

    public function setUp(): void
    {
        parent::setUp();

        $this->resourceType = 'events';

        $this->tour = Tour::factory()->create(['end_date' => now()->addYear()]);
        $this->event = Event::factory()
            ->for($this->tour, 'eventable')
            ->make(['date_time' => now()->addMonth()->format('Y-m-d H:i:s')]);
        
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('Admin');
        $this->operatorUser = User::factory()->create();
        $this->operatorUser->assignRole('Operator');

        $this->requiredFields = [
            'attributes' => [
                'dateTime' => 'Y-m-d H:i:s'
            ],
            'relationships' => [
                'product'
            ]
        ];

        $this->acceptedFields = [
            'relationships' => [
                'schedule'
            ]
        ];

        $this->correctAttributes = [
            'dateTime' => $this->event->date_time
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

    public function test_creating_an_event_requires_these_fields()
    {
        // $this->withoutExceptionHandling();

        $data = [
            'type' => $this->resourceType,
            'attributes' => [
                'dateTime' => ''
            ]
        ];

        foreach ($this->requiredFields['attributes'] as $fieldName => $format) {
            $detail = $fieldName !== $format ? "must match the format {$format}" : "is required";
            $snakeFieldName = Str::snake($fieldName, ' ');
            $expectedErrors[] = [
                "detail" => "The {$snakeFieldName} field {$detail}.",
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
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->post(route(('v1.events.store')));

        $response->assertErrors(422, $expectedErrors);
    }

    public function test_creating_an_event_is_forbidden_for_anonymous_users()
    {
        $data = [
            'type' => $this->resourceType,
            'attributes' => [
                'dateTime' => ''
            ]
        ];

        $response = $this
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->post(route(('v1.events.store')));
        
        $response->assertErrorStatus(['status' => '401']);
    }

    public function test_creating_an_event_is_forbidden_for_operator_users()
    {
        $data = [
            'type' => $this->resourceType,
            'attributes' => [
                'dateTime' => ''
            ]
        ];

        $response = $this
            ->actingAs($this->operatorUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->post(route(('v1.events.store')));
        
        $response->assertErrorStatus(['status' => '403']);
    }

    public function test_creating_an_event_is_allowed_for_admin_users()
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
            ->post(route(('v1.events.store')));
        
        $id = $response->assertCreatedWithServerId(
                route('v1.events.index'),
                $data
        )->id();

        $this->assertDatabaseHas('events', ['id' => $id]);
    }
}
