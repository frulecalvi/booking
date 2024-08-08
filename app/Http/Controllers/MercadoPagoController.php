<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Services\MercadoPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class MercadoPagoController extends Controller
{
    public function webhook(Request $request, MercadoPago $mercadoPago)
    {
        Log::info($request->getContent());

//        dd($request->query('account'));
        if (! $paymentMethod = PaymentMethod::find($request->query('paymentMethodId')))
            abort(400);

        $webhookSecret = $paymentMethod->secrets['webhook_secret'];
        $xRequestId = $request->header('x-request-id');
        $xSignature = $request->header('x-signature');
        $dataId = $request->query('data_id');

        try {
            $webhookValidation = $mercadoPago->validateWebhookNotification(
                webhookSecret: $webhookSecret,
                xRequestId: $xRequestId,
                xSignature: $xSignature,
                dataId: $dataId,
            );
        } catch (BadRequestException $e) {
            abort(400, $e->getMessage());
        }

        response();
    }
}
