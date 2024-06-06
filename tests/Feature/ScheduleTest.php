<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\Tour;
use App\Models\User;
use App\States\Schedule\Active;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected $resourceType;
    protected $tour;
    protected $schedules;
    protected $correctAttributes;
    protected $adminUser;
    protected $operatorUser;

    public function setUp(): void
    {
        parent::setUp();

        $this->resourceType = 'schedules';

        $this->tour = Tour::factory()->create();

        // dd(Schedule::getStatesFor('state')->toArray());

        $this->schedules = [];
        foreach (Schedule::getStatesFor('state')->toArray() as $state) {
            $this->schedules[$state] = [
                Schedule::factory(['state' => $state])
                    ->for($this->tour, 'scheduleable')
                    ->make(),
                Schedule::factory(['state' => $state])
                    ->for($this->tour, 'scheduleable')
                    ->make(),
                Schedule::factory(['state' => $state])
                    ->for($this->tour, 'scheduleable')
                    ->make(),
            ];
        }

        $this->correctAttributes = [
            'period' => $this->schedules[Active::$name][0]->period,
            'day' => $this->schedules[Active::$name][0]->day,
            'time' => $this->schedules[Active::$name][0]->time,
            'start_date' => $this->schedules[Active::$name][0]->start_date,
            'end_date' => $this->schedules[Active::$name][0]->end_date,
            'state' => $this->schedules[Active::$name][0]->state,
        ];

        $this->adminUser = User::factory()->create();
        $this->operatorUser = User::factory()->create();
        $this->adminUser->assignRole('Admin');
        $this->operatorUser->assignRole('Operator');
    }

    public function test_fetching_schedules_is_forbidden_for_unauthenticated_users()
    {
        // $this->withoutExceptionHandling();

        $response = $this
            ->jsonApi()
            ->expects('schedules')
            ->get(route('v1.schedules.index'));

            // var_dump($response);
        
        $response->assertErrorStatus(['status' => '401']);
    }

    public function test_operator_users_can_fetch_only_active_resources()
    {
        // $this->withoutExceptionHandling();

        foreach ($this->schedules as $schedules) {
            foreach ($schedules as $schedule)
                $schedule->save();
        }

        $response = $this
            ->actingAs($this->operatorUser)
            ->jsonApi()
            ->expects('schedules')
            ->get(route('v1.schedules.index'));

            // var_dump($response);
        
        $response->assertFetchedMany($this->schedules[Active::$name]);
    }

    public function test_admin_users_can_fetch_resources_with_any_state()
    {
        // $this->withoutExceptionHandling();

        $createdSchedules = [];

        foreach ($this->schedules as $schedules) {
            array_push($createdSchedules, ...$schedules);

            foreach ($schedules as $schedule)
                $schedule->save();
        }

        $response = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects('schedules')
            ->get(route('v1.schedules.index'));

        // var_dump($createdSchedules);
        
        $response->assertFetchedMany($createdSchedules);
    }
}
