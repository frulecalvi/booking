<?php

namespace Tests\Feature;

use App\Jobs\HandleMercadoPagoWebhookRequest;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Schedule;
use App\Models\Tour;
use App\Services\MercadoPago;
use App\States\Schedule\Active as ScheduleActive;
use App\States\Tour\Active as TourActive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Queue;
use MercadoPago\Client\Preference\PreferenceClient;
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

//    public function test_mercadopago_create_preference_returns_meta_data_with_preference_id()
//    {
//
//    }

    public function test_mercadopago_webhook_endpoint_is_defined()
    {
        $response = $this->postJson(route('mercadopagoWebhook'));

//        dd($response);

        $this->assertNotEquals(404, $response->status());
        $this->assertNotEquals(405, $response->status());
    }

    public function test_mercadopago_webhook_endpoint_responds_200_and_dispatches_job_handler_only_when_request_is_valid(): void
    {
        Queue::fake();

        $secret = $this->paymentMethod->secrets['webhook_secret'];
        $dataId = 'some-fake-id';
        $xRequestId = 'some-request-id';
        $ts = round(microtime(true));
        $manifest = "id:{$dataId};request-id:{$xRequestId};ts:{$ts};";
        $xSignature = "ts={$ts},v1=" . hash_hmac('sha256', $manifest, $secret);
        $wrongXSignature = "ts={$ts},v1=a" . hash_hmac('sha256', $manifest, $secret);

        $notValidResponse = $this
            ->withHeaders([
                'x-request-id' => $xRequestId,
                'x-signature' => $wrongXSignature,
            ])
            ->postJson(route('mercadopagoWebhook', [
                'paymentMethodId' => $this->paymentMethod->id,
                'data.id' => $dataId,
            ]));

        $notValidResponse->assertStatus(400);
        Queue::assertNotPushed(HandleMercadoPagoWebhookRequest::class);

        $validResponse = $this
            ->withHeaders([
                'x-request-id' => $xRequestId,
                'x-signature' => $xSignature,
            ])
            ->postJson(route('mercadopagoWebhook', [
                'paymentMethodId' => $this->paymentMethod->id,
                'data.id' => $dataId,
            ]));

        $validResponse->assertStatus(200);
        Queue::assertPushed(HandleMercadoPagoWebhookRequest::class);
    }
}
