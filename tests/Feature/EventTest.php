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

    public function test_fetching_an_event_includes_meta_data_with_its_total_availability()
    {
        $this->withoutExceptionHandling();
        
        $tour = Tour::factory()->create(
            ['end_date' => now()->addYear(),
            'state' => TourActive::$name,
            'capacity' => 55
        ]);

        $price = Price::factory()->for($tour, 'priceable')->create(['capacity' => 25]);
        $price2 = Price::factory()->for($tour, 'priceable')->create(['capacity' => 48]);
        $price3 = Price::factory()->for($tour, 'priceable')->create(['capacity' => 0]);

        $event = Event::factory()
            ->for($tour, 'eventable')
            ->create(['date_time' => now()->addWeek()]);

        $booking = Booking::factory()
            ->for($event)
            ->for($tour, 'bookingable')
            ->create();

        $tickets = Ticket::factory(2)
            ->for($booking)
            ->for($price)
            ->create(['quantity' => 1]);

        $tickets2 = Ticket::factory(3)
            ->for($booking)
            ->for($price2)
            ->create(['quantity' => 3]);

        $tickets3 = Ticket::factory(2)
            ->for($booking)
            ->for($price2)
            ->create(['quantity' => 4]);

        // dd($booking);

        // dd($tour->events->first());

        $tourPrices = $tour->prices;
        
        $availability = $tour->capacity;

        // $eventTickets = $event->tickets;
        // dd($eventTickets);

        $booked = 0;

        foreach ($tourPrices as $price) {
            $priceTickets = $event->tickets->where('price_id', '=', $price->id);

            // var_dump($priceTickets);

            foreach ($priceTickets as $currentTicket) {
                $booked += $currentTicket->quantity;
            }
        }

        $availability -= $booked;

        // die;

        // dd([$tour->capacity, $booked, $availability]);

        // dd($tour->events->last());
        // array_shift($meta['availableDates']);
        
        $response = $this
            ->jsonApi()
            ->expects('events')
            // ->query([
            //     'filter[event]' => $tourDate
            // ])
            ->get(route('v1.events.show', $event->getRouteKey()));

        // dd($response->getContent());
        $response->assertFetchedOne($event)
            ->assertJson(['data' => ['meta' => ['availability' => ['total' => $availability]]]]);
    }

    public function test_fetching_an_event_includes_meta_data_with_each_one_of_its_prices_availability()
    {
        $this->withoutExceptionHandling();
        
        $tour = Tour::factory()->create(['end_date' => now()->addYear(), 'state' => TourActive::$name]);
        $price = Price::factory()->for($tour, 'priceable')->create(['capacity' => 25]);
        $price2 = Price::factory()->for($tour, 'priceable')->create(['capacity' => 48]);
        $price3 = Price::factory()->for($tour, 'priceable')->create(['capacity' => 0]);

        $event = Event::factory()
            ->for($tour, 'eventable')
            ->create(['date_time' => now()->addWeek()]);

        $booking = Booking::factory()
            ->for($event)
            ->for($tour, 'bookingable')
            ->create();

        $tickets = Ticket::factory(2)
            ->for($booking)
            ->for($price)
            ->create(['quantity' => 1]);

        $tickets2 = Ticket::factory(3)
            ->for($booking)
            ->for($price2)
            ->create(['quantity' => 3]);

        $tickets3 = Ticket::factory(2)
            ->for($booking)
            ->for($price3)
            ->create(['quantity' => 4]);

        // dd($booking);

        // dd($tour->events->first());

        $tourPrices = $tour->prices;
        
        $pricesAvailability = [];

        // $eventTickets = $event->tickets;
        // dd($eventTickets);

        foreach ($tourPrices as $price) {
            if ($price->capacity === 0) {
                $pricesAvailability[$price->id] = 0;
                continue;
            }
            
            $priceTickets = $event->tickets->where('price_id', '=', $price->id);
            // var_dump($priceTickets);

            $booked = 0;
            foreach ($priceTickets as $currentTicket) {
                $booked += $currentTicket->quantity;
            }
            // var_dump($booked);

            $pricesAvailability[$price->id] = $price->capacity - $booked;
        }

        // dd([$price->capacity, $booked, $pricesAvailability[$price->id]]);

        // dd($tour->events->last());
        // array_shift($meta['availableDates']);
        
        $response = $this
            ->jsonApi()
            ->expects('events')
            // ->query([
            //     'filter[event]' => $tourDate
            // ])
            ->get(route('v1.events.show', $event->getRouteKey()));

        // dd($response->getContent());
        $response->assertFetchedOne($event)
            ->assertJson(['data' => ['meta' => ['availability' => ['prices' => $pricesAvailability]]]]);
        
        $this->assertCount(count($response->json()['data']['meta']['availability']['prices']), $pricesAvailability);
    }
}
