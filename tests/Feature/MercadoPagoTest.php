<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Schedule;
use App\Models\Tour;
use App\Services\MercadoPago;
use App\States\Schedule\Active as ScheduleActive;
use App\States\Tour\Active as TourActive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use MercadoPago\Client\Preference\PreferenceClient;
use Tests\TestCase;

class MercadoPagoTest extends TestCase
{
    use RefreshDatabase;

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

        $this->correctRelationships = [
            'booking' => [
                'data' => [
                    'type' => 'bookings',
                    'id' => $this->booking->id,
                ],
            ],
        ];
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

    public function test_mercadopago_webhook_endpoint_responds_200_when_valid_request(): void
    {
//        $this->withoutExceptionHandling();

        $secret = $this->paymentMethod->secrets['webhook_secret'];
//        dd($secret);
        $dataId = 'some-fake-id';
        $ts = round(microtime(true));
        $xRequestId = 'some-request-id';
        $manifest = "id:{$dataId};request-id:{$xRequestId};ts:{$ts};";
        $xSignature = "ts={$ts},v1=" . hash_hmac('sha256', $manifest, $secret);

        $response = $this
            ->withHeaders([
                'x-request-id' => $xRequestId,
                'x-signature' => $xSignature,
            ])
            ->postJson(route('mercadopagoWebhook', [
                'paymentMethodId' => $this->paymentMethod->id,
                'data.id' => $dataId,
            ]));

        $response->assertStatus(200);
    }
}
