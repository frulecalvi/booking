<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\Tour;
use App\States\Event\Inactive as EventInactive;
use App\States\Schedule\Active as ScheduleActive;
use App\States\Tour\Active as TourActive;
use DateInterval;
use DateTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TourEventTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    

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
                    'date' => $event->date,
                    'time' => $event->time
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
            $event->date = '1990-06-06';
            $event->save();
        }

        $included = [];

        foreach ($futureEvents as $event) {
            $included[] = [
                'type' => 'events',
                'id' => $event->id,
                'attributes' => [
                    'date' => $event->date,
                    'time' => $event->time
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
