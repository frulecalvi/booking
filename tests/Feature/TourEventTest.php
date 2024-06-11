<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Schedule;
use App\Models\Scopes\EventDateScope;
use App\Models\Tour;
use App\States\Event\Inactive as EventInactive;
use App\States\Schedule\Active as ScheduleActive;
use App\States\Tour\Active as TourActive;
use Carbon\Carbon;
use DateInterval;
use DateTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TourEventTest extends TestCase
{
    // use RefreshDatabase;

    protected $tour;
    protected $schedule;

    public function setUp(): void
    {
        parent::setUp();

        $this->tour = Tour::factory()->create(['state' => TourActive::$name, 'end_date' => now()->addYear()]);
        $this->schedule = Schedule::factory()
            ->for($this->tour, 'scheduleable')
            ->create(['state' => ScheduleActive::$name, 'period' => 'weekly']);
    }
    
    public function test_fetching_a_single_tour_includes_meta_data_with_available_dates_for_a_period_of_30_days()
    {
        $this->withoutExceptionHandling();
        
        $tour = Tour::factory()->create(['state' => TourActive::$name]);

        $today = new DateTime();
        $futureDate = $today->add(DateInterval::createFromDateString('365 days'))->format('Y-m-d');

        $tour->end_date = $futureDate;
        $tour->save();

        $schedule = Schedule::factory()
            ->for($tour, 'scheduleable')
            ->create(['period' => 'weekly', 'state' => ScheduleActive::$name]);

        $tour->events->first()->date_time = now();
        $tour->events->first()->save();
        $tour->events->last()->refresh();
        $tour->events->last()->date_time = now()->addDays(30);
        $tour->events->last()->save();
        $tour->events->last()->refresh();

        // dd($tour->events->first());

        $tourEvents = $tour->events;
        
        $availableDates = [];

        foreach ($tourEvents as $event) {
            if (
                $event->date_time > now()->addHour()
                && $event->date_time <= now()->addDays(30)
            )
                $availableDates[] = Carbon::parse($event->date_time)->format('Y-m-d');
        }

        // dd($tour->events->last());
        // array_shift($meta['availableDates']);
        
        $response = $this
            ->jsonApi()
            ->expects('tours')
            ->get(route('v1.tours.show', $tour->getRouteKey()));

        // dd($response->getContent());
        $response->assertFetchedOne($tour)
            ->assertJson(['data' => ['meta' => ['availableDates' => $availableDates]]]);
        
        $this->assertCount(count($response->json()['data']['meta']['availableDates']), $availableDates);
    }

    public function test_fetching_a_tour_s_related_events_is_allowed_for_unauthenticated_users()
    {
        $expected = [];

        foreach ($this->tour->events as $event) {
            $expected[] = [
                'type' => 'events',
                'id' => $event->id
            ];
        }

        $response = $this
            ->jsonApi()
            ->expects('tours')
            ->get(route('v1.tours.events', $this->tour->getRouteKey()));

        // dd($response->getContent());
        $response->assertFetchedMany($expected);
    }

    public function test_fetching_a_tour_s_related_events_allows_filtering_by_date()
    {
        $this->withoutExceptionHandling();

        $tourId = $this->tour->events()->first()->id;
        $tourDate = $this->tour->events()->first()->date_time;

        $expected = [
            [
                'type' => 'events',
                'id' => $tourId
            ]
        ];

        $response = $this
            ->jsonApi()
            ->expects('tours')
            ->query([
                'filter[events.date]' => $tourDate
            ])
            ->get(route('v1.tours.events', $this->tour->getRouteKey()));

        // dd($response->getContent());
        $response->assertFetchedMany($expected);
    }

    public function test_fetching_a_tour_related_events_includes_date_and_time_attributes()
    {
        $expected = [];

        foreach ($this->tour->events as $event) {
            $expected[] = [
                'type' => 'events',
                'id' => $event->id,
                'attributes' => [
                    'dateTime' => $event->date_time,
                ]
            ];
        }

        $response = $this
            ->jsonApi()
            ->expects('tours')
            ->get(route('v1.tours.events', $this->tour->getRouteKey()));

        // dd($response->getContent());
        $response->assertFetchedMany($expected);
    }

    public function test_attaching_a_tour_event_is_not_allowed_for_unauthenticated_users()
    {
        $events = Event::factory(2)
            ->for($this->schedule)
            ->create(['date_time' => now()->addDay()]);

            // dd($events);

        foreach ($events as $event) {
            $data[] = [
                'type' => 'events',
                'id' => $event->id
            ];
        }


        // $expected = $events->map(fn(Event $tag) => [
        //     'type' => 'events',
        //     'id' => (string) $tag->getRouteKey()
        // ])->all();

        // dd($data);

        $response = $this
            ->jsonApi()
            ->expects('events')
            ->withData($data)
            ->post(route('v1.tours.events.attach', $this->tour->getRouteKey()));

        $response->assertErrorStatus(['status' => '401']);        
    }

    public function test_updating_tour_event_is_not_allowed_for_unauthenticated_users()
    {
        $event = Event::factory()
            ->for($this->schedule)
            ->create(['date_time' => now()->addDay()]);

        $data = [
            [
                'type' => 'events',
                'id' => $event->id
            ]
        ];

        $response = $this
            ->jsonApi()
            ->expects('events')
            ->withData($data)
            ->patch(route('v1.tours.events.update', $this->tour->getRouteKey(), $event->getRouteKey()));

        $response->assertErrorStatus(['status' => '401']);        
    }

    public function test_detaching_tour_event_is_not_allowed_for_unauthenticated_users()
    {
        $event = Event::factory()
            ->for($this->schedule)
            ->create(['date_time' => now()->addDay()]);

        $data = [
            [
                'type' => 'events',
                'id' => $event->id
            ]
        ];

        $response = $this
            ->jsonApi()
            ->expects('events')
            ->withData($data)
            ->delete(route('v1.tours.events.detach', $this->tour->getRouteKey()));

        $response->assertErrorStatus(['status' => '401']);        
    }

    public function test_fetching_a_single_tour_with_active_associated_events_is_allowed_for_anonymous_users()
    {
        $tour = Tour::factory()->create(['state' => TourActive::$name]);

        $today = new DateTime();
        $futureDate = $today->add(DateInterval::createFromDateString('365 days'))->format('Y-m-d');

        $tour->end_date = $futureDate;
        $tour->save();

        $schedule = Schedule::factory()
            ->for($tour, 'scheduleable')
            ->create(['period' => 'weekly', 'state' => ScheduleActive::$name]);

        $tourEvents = $tour->events;

        $inactiveEvents = $tourEvents->slice(0, 2);
        $activeEvents = $tourEvents->slice(2);

        foreach ($inactiveEvents as $event) {
            $event->state = EventInactive::$name;
            $event->save();
        }
        
        $included = [];

        foreach ($activeEvents as $event) {
            $included[] = ['type' => 'events', 'id' => $event->id];
        }
        
        $response = $this
            ->jsonApi()
            ->expects('tours')
            ->includePaths('events')
            ->get(route('v1.tours.show', $tour->getRouteKey()));

        // dd($response->getContent());
        $response->assertFetchedOne($tour)
            ->assertIncluded($included);
    }

    public function test_fetching_tour_events_includes_date_and_time_attributes()
    {
        $today = new DateTime();
        $futureDate = $today->add(DateInterval::createFromDateString('365 days'))->format('Y-m-d');

        $tour = Tour::factory()->create(['end_date' => $futureDate, 'state' => TourActive::$name]);
        $schedule = Schedule::factory()
            ->for($tour, 'scheduleable')
            ->create(['period' => 'weekly', 'state' => ScheduleActive::$name]);
        
        $events = $tour->events;

        $included = [];

        foreach ($events as $event) {
            $included[] = [
                'type' => 'events',
                'id' => $event->id,
                'attributes' => [
                    'dateTime' => $event->date_time,
                ]
            ];
        }

        $response = $this
            ->jsonApi()
            ->expects('tours')
            ->includePaths('events')
            ->get(route('v1.tours.show', $tour->getRouteKey()));

        // dd($response->getContent());
        $response->assertFetchedOne($tour)
            ->assertIncluded($included);
    }

    public function test_fetching_tour_includes_only_future_events()
    {
        $today = new DateTime();
        $futureDate = $today->add(DateInterval::createFromDateString('30 days'))->format('Y-m-d');

        $tour = Tour::factory()->create(['end_date' => $futureDate, 'state' => TourActive::$name]);
        $schedule = Schedule::factory()
            ->for($tour, 'scheduleable')
            ->create(['period' => 'weekly', 'state' => ScheduleActive::$name]);
        
        $events = $tour->events;

        $pastEvents = $events->slice(0, 2);
        $futureEvents = $events->slice(2);

        foreach ($pastEvents as $event) {
            $event->date_time = '1990-06-06';
            $event->save();
        }

        $included = [];

        foreach ($futureEvents as $event) {
            $included[] = [
                'type' => 'events',
                'id' => $event->id,
                'attributes' => [
                    'dateTime' => $event->date_time,
                ]
            ];
        }

        $response = $this
            ->jsonApi()
            ->expects('tours')
            ->includePaths('events')
            ->get(route('v1.tours.show', $tour->getRouteKey()));

        // dd($response->getContent());
        $response->assertFetchedOne($tour)
            ->assertIncluded($included);
    }
}
