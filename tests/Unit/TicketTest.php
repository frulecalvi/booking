<?php

namespace Tests\Unit;

use App\Jobs\FindExpiredBookings;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Price;
use App\Models\Schedule;
use App\Models\Ticket;
use App\Models\Tour;
use App\Services\BookingService;
use App\States\Booking\Pending as BookingPending;
use App\States\Booking\Expired as BookingExpired;
use App\States\Schedule\Active as ScheduleActive;
use App\States\Tour\Active as TourActive;
use Illuminate\Console\Scheduling\Schedule as LaravelSchedule;
use Illuminate\Console\Scheduling\Event as LaravelEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    private array $tours;
    private array $schedules;

    public function setUp(): void
    {
        parent::setUp();

        $this->tour = Tour::factory()
            ->create([
                'end_date' => now()->addYear(),
                'state' => TourActive::$name,
                'capacity' => 60,
            ]);
        $this->price = Price::factory()
            ->for($this->tour, 'priceable')
            ->create([
                'capacity' => 30,
            ]);
        $this->event = Event::factory()
            ->for($this->tour, 'eventable')
            ->create(['date_time' => now()->addWeek()]);
        $this->booking = Booking::factory()
            ->for($this->event)
            ->for($this->tour, 'bookingable')
            ->create();
        $this->ticket = Ticket::factory()
            ->for($this->booking)
            ->for($this->price)
            ->make();
    }


    public function test_creating_a_ticket_saves_current_price_amount()
    {
        $this->assertEquals(0, $this->ticket->price_amount);

        $this->ticket->save();
        $this->ticket->refresh();

        $this->assertEquals($this->ticket->price->amount, $this->ticket->price_amount);
    }
}
