<?php

namespace App\Services;

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

    public function createPreferenceForPayment($id): string
    {
        $client = new PreferenceClient();

        try {
            $preference = $client->create([
                "items" => [
                    [
                        "title" => "Mi producto",
                        "quantity" => 1,
                        "unit_price" => 2000,
                    ],
                ],
                'notification_url' => "http://url.webhook/{$id}",
            ]);
        } catch (MPApiException $exception) {
            throw \Exception($exception->getMessage());
        }

        return $preference->id;
    }
}
