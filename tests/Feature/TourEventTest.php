<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\Tour;
use App\States\Schedule\Active as ScheduleActive;
use App\States\Tour\Active as TourActive;
use DateInterval;
use DateTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TourEventTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    
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
}
