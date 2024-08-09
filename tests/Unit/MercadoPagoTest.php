<?php

namespace Tests\Unit;

use App\Jobs\CreatePayment;
use App\Jobs\HandleMercadoPagoWebhookRequest;
use App\Models\Booking;
use App\Models\Event;
use App\Models\PaymentMethod;
use App\Models\Schedule;
use App\Models\Tour;
use App\Services\MercadoPago;
use App\States\Schedule\Active as ScheduleActive;
use App\States\Tour\Active as TourActive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use Tests\TestCase;

class MercadoPagoTest extends TestCase
{
    use RefreshDatabase;

    private Tour $tour;
    private Schedule $schedule;
    private Event $event;
    private Booking $booking;
    private PaymentMethod $paymentMethod;
    private array $correctRelationships;

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

        $this->paymentMethod = PaymentMethod::factory()
            ->hasAttached(
                $this->tour
            )
            ->create([
                'payment_method_type' => 'mercadopago',
                'secrets' => [
                    'access_token' => 'some_fake_access_token',
                    'webhook_secret' => 'some_fake_webhook_secret',
                ]
            ]);
    }

    public function test_mercadopago_webhook_handler_dispatches_payment_creation_only_when_valid_request(): void
    {
        $secret = $this->paymentMethod->secrets['webhook_secret'];
        $dataId = 'some-fake-id';
        $ts = round(microtime(true));
        $xRequestId = 'some-request-id';
        $manifest = "id:{$dataId};request-id:{$xRequestId};ts:{$ts};";
        $xSignature = "ts={$ts},v1=" . hash_hmac('sha256', $manifest, $secret);
        $wrongXSignature = "ts={$ts},v1=a" . hash_hmac('sha256', $manifest, $secret);

        $mockPayment = new \stdClass();
        $mockPayment->metadata = new \stdClass();
        $mockPayment->metadata->booking_id = $this->booking->id;
        $mockPayment->metadata->payment_method_id = $this->paymentMethod->id;

        $mock = $this->partialMock(MercadoPago::class, function (MockInterface $mock) use ($dataId, $mockPayment) {
            $mock
                ->shouldReceive('getPayment')
                ->with($dataId)
                ->andReturn($mockPayment)
                ->once();
        });

        HandleMercadoPagoWebhookRequest::dispatch(
            $xRequestId,
            $wrongXSignature,
            $dataId,
            $this->paymentMethod->id
        );

        $this->assertDatabaseCount('payments', 0);

        HandleMercadoPagoWebhookRequest::dispatch(
            $xRequestId,
            $xSignature,
            $dataId,
            $this->paymentMethod->id
        );

        $this->assertDatabaseHas('payments', ['booking_id' => $this->booking->id]);
    }
}
