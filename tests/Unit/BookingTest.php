<?php

namespace Tests\Unit;

use App\Jobs\FindExpiredBookings;
use App\Models\Booking;
use App\Models\Price;
use App\Models\Schedule;
use App\Models\Ticket;
use App\Models\Tour;
use App\Services\BookingService;
use App\States\Booking\Pending as BookingActive;
use App\States\Booking\Expired as BookingExpired;
use App\States\Schedule\Active as ScheduleActive;
use App\States\Tour\Active as TourActive;
use Illuminate\Console\Scheduling\Schedule as LaravelSchedule;
use Illuminate\Console\Scheduling\Event as LaravelEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private array $tours;
    private array $schedules;

    public function setUp(): void
    {
        parent::setUp();

        $this->tours = [
            Tour::factory()->create([
                'book_without_payment' => true,
                'end_date' => now()->addYear(),
                'state' => TourActive::$name,
            ]),
            Tour::factory()->create([
                'book_without_payment' => true,
                'end_date' => now()->addYear(),
                'state' => TourActive::$name,
            ]),
            Tour::factory()->create([
                'book_without_payment' => false,
                'end_date' => now()->addYear(),
                'state' => TourActive::$name,
            ]),
            Tour::factory()->create([
                'book_without_payment' => false,
                'end_date' => now()->addYear(),
                'state' => TourActive::$name,
            ]),
        ];

        $this->schedules = [
            Schedule::factory()
                ->for($this->tours[0], 'scheduleable')
                ->create([
                    'period' => 'weekly',
                ]),
            Schedule::factory()
                ->for($this->tours[1], 'scheduleable')
                ->create([
                    'period' => 'weekly',
                ]),
            Schedule::factory()
                ->for($this->tours[2], 'scheduleable')
                ->create([
                    'period' => 'weekly',
                ]),
            Schedule::factory()
                ->for($this->tours[3], 'scheduleable')
                ->create([
                    'period' => 'weekly',
                ]),
        ];
    }

    public function test_when_booking_tickets_are_created_updated_or_deleted_the_total_price_is_updated()
    {
        $schedule = Schedule::factory()
            ->for($this->tours[0], 'scheduleable')
            ->create(['state' => ScheduleActive::$name, 'date' => now()->addDays(15)]);
        $event = $this->tours[0]->events->first();

        $booking = Booking::factory()
            ->for($this->tours[0], 'bookingable')
            ->for($event)
            ->make();

        $this->assertEquals(0, $booking->total_price);

        $booking->save();

        $prices = Price::factory(3)
            ->for($booking->bookingable, 'priceable')
            ->create();

        $tickets = [];

        foreach ($prices as $price) {
            $tickets[] = Ticket::factory(1)
                ->for($booking)
                ->for($price)
                ->create();
        }

        $booking->refresh();

        $bookingService = new BookingService();

        $totalPrice = $bookingService->calculateTotalPrice($booking);
        $this->assertEquals($totalPrice, $booking->total_price);

        $tickets[0][0]->delete();
        $booking->refresh();

        $this->assertNotEquals($totalPrice, $booking->total_price);
        $totalPrice = $bookingService->calculateTotalPrice($booking);
        $this->assertEquals($totalPrice, $booking->total_price);

        $tickets[1][0]->quantity = $tickets[1][0]->quantity + 1;
        $tickets[1][0]->save();
//        var_dump($booking->tickets);
        $booking->refresh();
//        var_dump($booking->tickets);

        $this->assertNotEquals($totalPrice, $booking->total_price);
        $totalPrice = $bookingService->calculateTotalPrice($booking);
        $this->assertEquals($totalPrice, $booking->total_price);
    }

    public function test_bookings_older_than_15_minutes_are_marked_as_expired_by_job_if_product_demands_payment()
    {
        $bookings = [
            Booking::factory()
                ->for($this->tours[0], 'bookingable')
                ->for($this->tours[0]->events()->first())
                ->create(['state' => BookingActive::$name]),
            Booking::factory()
                ->for($this->tours[1], 'bookingable')
                ->for($this->tours[1]->events()->first())
                ->create(['state' => BookingActive::$name, 'created_at' => now()->subMinutes(18)]),
            Booking::factory()
                ->for($this->tours[2], 'bookingable')
                ->for($this->tours[2]->events()->first())
                ->create(['state' => BookingActive::$name]),
            Booking::factory()
                ->for($this->tours[3], 'bookingable')
                ->for($this->tours[3]->events()->first())
                ->create(['state' => BookingActive::$name, 'created_at' => now()->subMinutes(15)]),
        ];

        FindExpiredBookings::dispatch();

        $this->assertDatabaseHas('bookings', ['id' => $bookings[0]->id, 'state' => BookingActive::$name]);
        $this->assertDatabaseHas('bookings', ['id' => $bookings[1]->id, 'state' => BookingActive::$name]);
        $this->assertDatabaseHas('bookings', ['id' => $bookings[2]->id, 'state' => BookingActive::$name]);
        $this->assertDatabaseHas('bookings', ['id' => $bookings[3]->id, 'state' => BookingExpired::$name]);
    }

    public function test_find_expired_bookings_job_is_scheduled_to_run_every_minute()
    {
        $schedule = app()->make(LaravelSchedule::class);

        $events = collect($schedule->events())->filter(function (LaravelEvent $event) {
            return str_contains($event->description, 'FindExpiredBookings');
        });

        $this->assertGreaterThan(0, $events->count());

        $events->each(function (LaravelEvent $event) {
            $this->assertEquals('* * * * *', $event->expression);
        });
    }
}
