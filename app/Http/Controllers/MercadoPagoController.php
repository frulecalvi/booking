<?php

namespace App\Http\Controllers;

use App\Jobs\HandleMercadoPagoWebhookRequest;
use App\Models\Booking;
use App\Models\PaymentMethod;
use App\Services\MercadoPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class MercadoPagoController extends Controller
{
    public function webhook(Request $request, MercadoPago $mercadoPago)
    {
        Log::info("MercadoPago webhook call: {$request->getContent()}");

        $xRequestId = $request->header('x-request-id');
        $xSignature = $request->header('x-signature');
        $dataId = $request->query('data_id');
        $paymentMethodId = $request->query('paymentMethodId');

        if (! $xRequestId || ! $xSignature || ! $dataId || ! $paymentMethodId) {
            Log::warning("MercadoPago webhook call failed: received data is incomplete - paymentId: {$dataId} - xRequestId: {$xRequestId} - xSignature: {$xSignature} - paymentMethodId: {$paymentMethodId}");
            abort(400);
        }

        if (! $paymentMethod = PaymentMethod::find($request->query('paymentMethodId'))) {
            Log::error("MercadoPago webhook call failed: payment method not found - mpPaymentId: {$dataId} - paymentMethodId: {$paymentMethodId}");
            abort(400);
        }

        $webhookSecret = $paymentMethod->secrets['webhook_secret'];

        if (! $webhookSecret) {
            Log::error("MercadoPago webhook call failed: secrets not set - mpPaymentId: {$dataId} - paymentMethodId: {$this->paymentMethod->id}");
            abort(400);
        }

        try {
            $webhookValidation = $mercadoPago->validateWebhookNotification(
                webhookSecret: $webhookSecret,
                xRequestId: $xRequestId,
                xSignature: $xSignature,
                dataId: $dataId,
            );
        } catch (BadRequestException $e) {
            Log::error("MercadoPago webhook call failed: validation error - mpPaymentId: {$dataId} - paymentMethodId: {$paymentMethod->id}");
            abort(400);
        }

        HandleMercadoPagoWebhookRequest::dispatch(
            $dataId,
            $paymentMethod
        );

        return response(200);
    }
}
