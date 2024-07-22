<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Tour;
use App\Services\MercadoPago;
use App\States\Tour\Active as TourActive;
use App\States\Schedule\Active as ScheduleActive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MercadoPago\Client\Preference\PreferenceClient;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public Tour $tour;
    public Event $event;
    public Schedule $schedule;
    public Booking $booking;
    public Payment $payment;

    public $resourceType = 'payments';

    public $correctRelationships;

    public function setUp(): void
    {
        parent::setUp();

        $this->tour = Tour::factory()->create(['state' => TourActive::$name]);
        $this->schedule = Schedule::factory()
            ->for($this->tour, 'scheduleable')
            ->create(['period' => 'once', 'state' => ScheduleActive::$name, 'date' => now()->addDays(2)]);

//        dd(Event::all());
        $this->event = $this->schedule->events->first();
        $this->booking = Booking::factory()
            ->for($this->tour, 'bookingable')
            ->for($this->schedule)
            ->for($this->event)
            ->create();

        $this->payment = Payment::factory()
            ->for($this->booking)
            ->make();

        $this->correctRelationships = [
            'booking' => [
                'data' => [
                    'type' => 'bookings',
                    'id' => $this->booking->id,
                ],
            ],
        ];
    }

    public function test_creating_a_payment_with_its_related_booking_is_allowed_for_anonymous_users(): void
    {
        $this->withoutExceptionHandling();

        $data = [
            'type' => $this->resourceType,
            'relationships' => $this->correctRelationships,
        ];

        $response = $this
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->includePaths('booking')
            ->post(route('v1.payments.store'));

        $id = $response->assertCreatedWithServerId(
            route('v1.payments.index'),
            $data
        )->id();

        $this->assertDatabaseHas('payments', ['id' => $id]);
    }

    public function test_creating_a_payment_without_specified_booking_id_is_not_allowed(): void
    {
//        $this->withoutExceptionHandling();

        $data = [
            'type' => $this->resourceType,
        ];

        $response = $this
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->post(route('v1.payments.store'));

        $expectedError = [
            "detail" => "The booking field is required.",
            'source' => ['pointer' => "/data/relationships/booking"],
            'status' => '422',
            "title" => "Unprocessable Entity"
        ];

        $response->assertError('422', $expectedError);
    }

    public function test_fetching_payments_is_not_allowed_for_unauthenticated_users(): void
    {
        $response = $this
            ->jsonApi()
            ->expects($this->resourceType)
            ->get(route('v1.payments.index'));

        $response->assertErrorStatus(['status' => '401']);
    }

    public function test_calling_mercadopago_preference_endpoint_for_a_payment_returns_meta_with_valid_preference_id()
    {
        $this->withoutExceptionHandling();

        $this->payment->save();

        $response = $this
            ->jsonApi()
            ->expects($this->resourceType)
            ->post(route('v1.payments.mpCreatePreference', $this->payment->id));

//        dd($response);
        $preferenceId = $response->json('meta.preferenceId');

        $expectedMeta = [
            'preferenceId' => $preferenceId,
        ];

        $client = new PreferenceClient;
        $preference = $client->get($preferenceId);

        $this->assertEquals(200, $preference->getResponse()->getStatusCode());

        $response->assertExactMetaWithoutData($expectedMeta);
    }
}
