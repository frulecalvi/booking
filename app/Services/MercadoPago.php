<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Net\MPResponse;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class MercadoPago
{
    protected PreferenceClient $client;

    public function __construct()
    {
        MercadoPagoConfig::setAccessToken(config('mercadopago.access_token'));
    }

    /**
     * @throws \Exception
     */
    public function createPreferenceForBooking(Booking $booking): string
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
            ]);
        } catch (MPApiException $exception) {
            throw new \Exception($exception->getMessage());
        }

//        dd($client->get($preference->id));

        return $preference->id;
    }

    public function validateWebhookNotification(
        string $webhookSecret,
        string $xRequestId,
        string $xSignature,
        string $dataId
    ): bool
    {
        foreach (explode(",", $xSignature) as $part) {
            [$clave, $valor] = explode('=', $part);

            if ($clave === 'ts') {
                $ts = $valor;
            } elseif ($clave === 'v1') {
                $hash = $valor;
            }
        }

        if (! isset($ts) || ! isset($hash))
            throw new BadRequestException('x-signature header is not valid');

        $manifest = "id:{$dataId};request-id:{$xRequestId};ts:{$ts}";

        $sha = hash_hmac('sha256', $manifest, $webhookSecret);

        if ($sha !== $hash) {
            throw new BadRequestException('Request is not valid');
        }

        return true;
    }
}
