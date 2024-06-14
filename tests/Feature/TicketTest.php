<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Price;
use App\Models\Ticket;
use App\Models\Tour;
use App\Models\User;
use App\States\Tour\Active as TourActive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    protected $resourceType;

    protected $adminUser;
    protected $operatorUser;

    protected $tour;
    protected $tour2;
    protected $price;
    protected $price2;
    protected $event;
    protected $booking;
    protected $ticket;

    protected $requiredFields;
    protected $correctAttributes;
    protected $correctRelationships;
    protected $unrelatedResourcesRelationships;

    public function setUp(): void
    {
        parent::setUp();

        $this->resourceType = 'tickets';

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('Admin');
        $this->operatorUser = User::factory()->create();
        $this->operatorUser->assignRole('Operator');

        $this->tour = Tour::factory()->create(['end_date' => now()->addYear(), 'state' => TourActive::$name]);
        $this->price = Price::factory()->for($this->tour, 'priceable')->create();
        $this->event = Event::factory()->for($this->tour, 'eventable')->create(['date_time' => now()->addWeek()]);
        $this->booking = Booking::factory()
            ->for($this->event)
            ->for($this->tour, 'bookingable')
            ->create();
        $this->ticket = Ticket::factory()->make();

        $this->tour2 = Tour::factory()->create(['end_date' => now()->addYear(), 'state' => TourActive::$name]);
        $this->price2 = Price::factory()->for($this->tour2, 'priceable')->create();

        $this->requiredFields = [
            'attributes' => [
                'name',
                'person_id',
                'nationality',
                'quantity',
            ],
            'relationships' => [
                'booking',
                'price',
            ]
        ];

        $this->correctAttributes = [
            'name' => $this->ticket->name,
            'person_id' => $this->ticket->person_id,
            'nationality' => $this->ticket->nationality,
            'quantity' => $this->ticket->quantity,
        ];

        $this->correctRelationships = [
            'booking' => [
                'data' => [
                    'type' => 'bookings',
                    'id' => $this->booking->id
                ]
            ],
            'price' => [
                'data' => [
                    'type' => 'prices',
                    'id' => $this->price->id
                ]
            ],
        ];

        $this->unrelatedResourcesRelationships = [
            'booking' => [
                'data' => [
                    'type' => 'bookings',
                    'id' => $this->booking->id
                ]
            ],
            'price' => [
                'data' => [
                    'type' => 'prices',
                    'id' => $this->price2->id
                ]
            ],
        ];
    }

    public function test_creating_a_ticket_is_allowed_for_anonymous_users()
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
            ->includePaths(...array_keys($this->correctRelationships))
            ->post(route('v1.tickets.store'));

        $id = $response->assertCreatedWithServerId(
            route('v1.tickets.index'),
            $data
        )->id();

        $this->assertDatabaseHas($this->resourceType, ['id' => $id]);
    }

    public function test_creating_a_ticket_fails_if_any_of_the_resources_is_not_related_to_the_others()
    {
        // $this->withoutExceptionHandling();

        $data = [
            'type' => $this->resourceType,
            'attributes' => $this->correctAttributes,
            'relationships' => $this->unrelatedResourcesRelationships
        ];

        foreach ($this->requiredFields['relationships'] as $field) {
            if ($field !== 'price')
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
            ->post(route('v1.tickets.store'));

        // dd($response);

        $response->assertErrors(422, $expectedErrors);
    }
}
