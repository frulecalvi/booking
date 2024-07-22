<?php

namespace App\Services;

use App\Models\Payment;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Resources\Preference;

class MercadoPago
{
    protected PreferenceClient $client;

    public function __construct()
    {
        MercadoPagoConfig::setAccessToken(config('mercadopago.access_token'));
    }

    public function createPreferenceForPayment(Payment $payment): string
    {
        $client = new PreferenceClient();

//        dd($payment);

        try {
            $preference = $client->create([
                "items" => [
                    [
                        "title" => "Mi producto",
                        "quantity" => 1,
                        "unit_price" => 2000,
                    ],
                ],
                'notification_url' => route('v1.payments.mpUpdate', $payment),
            ]);
        } catch (MPApiException $exception) {
            throw new \Exception($exception->getMessage());
        }

//        dd($client->get($preference->id));

        return $preference->id;
    }
}
