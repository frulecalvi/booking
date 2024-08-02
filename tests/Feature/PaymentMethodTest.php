<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\PaymentMethod;
use App\Models\Price;
use App\Models\Schedule;
use App\Models\Ticket;
use App\Models\Tour;
use App\Models\User;
use App\Services\MercadoPago;
use App\States\Schedule\Active as ScheduleActive;
use App\States\Tour\Active as TourActive;
use GuzzleHttp\Promise\Create;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class PaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    private string $resourceType;
    private User $operatorUser;
    private User $adminUser;
    private Tour $tour;
    private MercadoPago $mercadoPago;
    private array $correctAttributes;

    public function setUp(): void
    {
        parent::setUp();

        $this->resourceType = 'payment-methods';

        $this->paymentMethods = [
            'mercadopago1' => PaymentMethod::factory()->create([
                'payment_method_type' => 'mercadopago',
                'secrets' => [
                    'access_token' => env('MP_TEST_ACCESS_TOKEN'),
                    'webhook_secret' => 'fake-secret',
                ]
            ]),
            'mercadopago2' => PaymentMethod::factory()->create(['payment_method_type' => 'mercadopago']),
        ];

        $this->operatorUser = User::factory()->create();
        $this->operatorUser->assignRole('Operator');
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('Admin');

        $this->tour = Tour::factory()->create(['state' => TourActive::$name, 'end_date' => now()->addYears(1)]);
        $this->schedule = Schedule::factory()->for($this->tour, 'scheduleable')->create(['state' => ScheduleActive::$name, 'date' => now()->addDays(15)]);
        $this->event = $this->tour->events->first();
        // dd([$this->tour->events, $this->schedule]);
        $this->booking = Booking::factory()
            ->for($this->event)
            ->for($this->schedule)
            ->for($this->tour, 'bookingable')
            ->make();

        $this->mercadoPago = new MercadoPago();

        $this->correctAttributes = [
            'name' => 'Prueba',
            'payment_method_type' => array_keys(config('payment_methods'))[array_rand(array_keys(config('payment_methods')))],
            'secrets' => [
                'cred1' => '1',
                'cred2' => '2',
            ]
        ];

//        dd($this->correctAttributes);
    }

    public function test_fetching_payment_methods_list_is_forbidden_for_anonymous_users()
    {
        // $this->withoutExceptionHandling();

        $response = $this
            ->jsonApi()
            ->expects($this->resourceType)
            ->get(route('v1.payment-methods.index'));

        // var_dump($response);

        $response->assertErrorStatus(['status' => '401']);
    }

    public function test_fetching_payment_methods_list_is_forbidden_for_operator_users()
    {
        // $this->withoutExceptionHandling();

        $response = $this
            ->actingAs($this->operatorUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->get(route('v1.payment-methods.index'));

        // var_dump($response);

        $response->assertErrorStatus(['status' => '403']);
    }

    public function test_fetching_payment_methods_list_is_allowed_for_admin_users()
    {
        // $this->withoutExceptionHandling();

        $response = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->get(route('v1.payment-methods.index'));

        // var_dump($response);

        $response->assertFetchedMany($this->paymentMethods);
    }

    public function test_fetching_a_payment_method_is_forbidden_for_anonymous_users()
    {
        // $this->withoutExceptionHandling();

//        dd($this->paymentMethods);

        $response = $this
            ->jsonApi()
            ->expects($this->resourceType)
            ->get(route('v1.payment-methods.show', $this->paymentMethods['mercadopago1']->getRouteKey()));

        // var_dump($response);

        $response->assertErrorStatus(['status' => '401']);
    }

    public function test_fetching_a_payment_method_is_forbidden_for_operator_users()
    {
        // $this->withoutExceptionHandling();

        $response = $this
            ->actingAs($this->operatorUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->get(route('v1.payment-methods.show', $this->paymentMethods['mercadopago1']->getRouteKey()));

        // var_dump($response);

        $response->assertErrorStatus(['status' => '403']);
    }

    public function test_fetching_a_payment_method_is_allowed_for_admin_users()
    {
        // $this->withoutExceptionHandling();

        $response = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->get(route('v1.payment-methods.show', $this->paymentMethods['mercadopago1']->getRouteKey()));

        // var_dump($response);

        $response->assertFetchedOne($this->paymentMethods['mercadopago1']);
    }

    public function test_creating_a_payment_method_is_forbidden_for_anonymous_users()
    {
        // $this->withoutExceptionHandling();

        $data = [
            'type' => 'payment-methods',
            'data' => []
        ];

        $response = $this
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->post(route('v1.payment-methods.store'));

        // var_dump($response);

        $response->assertErrorStatus(['status' => '401']);
    }

    public function test_creating_a_payment_method_is_forbidden_for_operator_users()
    {
        // $this->withoutExceptionHandling();

        $data = [
            'type' => 'payment-methods',
            'data' => []
        ];

        $response = $this
            ->actingAs($this->operatorUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->post(route('v1.payment-methods.store'));

        // var_dump($response);

        $response->assertErrorStatus(['status' => '403']);
    }

    public function test_creating_a_payment_method_is_allowed_for_admin_users()
    {
//        $this->withoutExceptionHandling();

        $data = [
            'type' => $this->resourceType,
            'attributes' => $this->correctAttributes
        ];

//        dd($data);

        $response = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->post(route('v1.payment-methods.store'));

//         dd($response->getContent());

        $id = $response->assertCreatedWithServerId(
            route('v1.payment-methods.index'),
            $data
        )->id();

        $this->assertDatabaseHas('payment_methods', ['id' => $id]);
    }

    public function test_creating_a_payment_method_saves_secret_values_encrypted()
    {
        $this->withoutExceptionHandling();

        $data = [
            'type' => $this->resourceType,
            'attributes' => $this->correctAttributes
        ];

//        dd($data);

        $response = $this
            ->actingAs($this->adminUser)
            ->jsonApi()
            ->expects($this->resourceType)
            ->withData($data)
            ->post(route('v1.payment-methods.store'));

//         dd($response->getContent());

        $id = $response->assertCreatedWithServerId(
            route('v1.payment-methods.index'),
            $data
        )->id();

//        dd(($this->correctAttributes['secrets']));

        $createdAccount = PaymentMethod::findOrFail($id);

        $this->assertEquals(
            $this->correctAttributes['secrets'],
            json_decode(Crypt::decryptString($createdAccount->getRawOriginal('secrets')), true)
        );
    }


    public function test_calling_mercadopago_payment_method_prepare_payment_endpoint_returns_meta_with_preference_id()
    {
//        $this->withoutExceptionHandling();

        $this->booking->save();

        $prices = Price::factory(3)
            ->for($this->booking->bookingable, 'priceable')
            ->create();

        foreach ($prices as $price) {
            $tickets = Ticket::factory(1)
                ->for($this->booking)
                ->for($price)
                ->create();
        }

        $response = $this
            ->jsonApi()
            ->expects($this->resourceType)
            ->post(
                route(
                    'v1.payment-methods.preparePayment',
                    [$this->paymentMethods['mercadopago1'], 'bookingId' => $this->booking->id]
                )
            );

//        dd($response);
        $preferenceId = $response->json('meta.preferenceId');

//        dd($response);

        $this->mercadoPago->setConfig($this->paymentMethods['mercadopago1']->secrets['access_token']);
        $preference = $this->mercadoPago->getPreference($preferenceId);

        $this->assertEquals(200, $preference->getStatusCode());

        $expectedMeta = [
            'preferenceId' => $preferenceId,
        ];

        $response->assertExactMetaWithoutData($expectedMeta);
    }

    public function test_calling_mercadopago_payment_method_prepare_payment_endpoint_creates_preference_with_correct_ext_ref_and_one_item_with_correct_amount()
    {
        $this->withoutExceptionHandling();

        $this->booking->save();
        $prices = Price::factory(3)
            ->for($this->booking->bookingable, 'priceable')
            ->create();

        foreach ($prices as $price) {
            $tickets = Ticket::factory(1)
                ->for($this->booking)
                ->for($price)
                ->create();
        }

        $totalPrice = 0;
        foreach ($this->booking->tickets as $ticket) {
            $totalPrice += $ticket->price->amount * $ticket->quantity;
        }

        $response = $this
            ->jsonApi()
            ->expects($this->resourceType)
            ->post(
                route(
                    'v1.payment-methods.preparePayment',
                    [$this->paymentMethods['mercadopago1'], 'bookingId' => $this->booking->id],
                )
            );

//        dd($response);
        $preferenceId = $response->json('meta.preferenceId');

        $this->mercadoPago->setConfig($this->paymentMethods['mercadopago1']->secrets['access_token']);
        $preference = $this->mercadoPago->getPreference($preferenceId);

//        dd($preference->getContent());

        $this->assertEquals($this->booking->id, $preference->getContent()['metadata']['bookingId']);
        $this->assertEquals($this->paymentMethods['mercadopago1']->id, $preference->getContent()['metadata']['paymentMethodId']);
        $this->assertEquals(1, count($preference->getContent()['items']));
        $this->assertEquals(roundPrice($totalPrice), $preference->getContent()['items'][0]['unit_price']);

//        dd($preference->getContent());
    }
}
