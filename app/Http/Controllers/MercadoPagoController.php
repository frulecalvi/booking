<?php

namespace App\Http\Controllers;

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

        if (! $xRequestId || ! $xSignature || ! $dataId) {
            Log::warning("MercadoPago webhook call failed: received data is incomplete - paymentId: {$dataId} - xRequestId: {$xRequestId} - xSignature: {$xSignature}");
            abort(400);
        }

        if (! $paymentMethod = PaymentMethod::find($request->query('paymentMethodId'))) {
            Log::error("MercadoPago webhook error: payment method not found - paymentId: {$dataId} - paymentMethodId: {$request->query('paymentMethodId')}");
            abort(400);
        }

        if (
            ! $webhookSecret = $paymentMethod->secrets['webhook_secret']
            || ! $accessToken = $paymentMethod->secrets['access_token']
        ) {
            Log::error("MercadoPago webhook error: webhook secret not set - paymentId: {$dataId} - paymentMethodId: {$paymentMethod->id}");
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
            Log::error("MercadoPago webhook error: validation error - paymentId: {$dataId} - paymentMethodId: {$paymentMethod->id}");
            abort(400, $e->getMessage());
        }

        $mercadoPago->setConfig($accessToken);

        try {
            $mpPayment = $mercadoPago->getPayment($dataId);
        } catch (BadRequestException $e) {
            Log::error("MercadoPago webhook error: {$e->getMessage()} - paymentId: {$dataId}");
            abort(400);
        }

        if (
            isset($mpPayment) && (
                ! $bookingId = $mpPayment['metadata']['booking_id']
                || ! $mpPayment['metadata']['payment_method_id'] !== $paymentMethod->id
            )
        ) {
            Log::error("MercadoPago webhook error: payment metadata inconsistency - paymentId: {$dataId}");
            abort(400);
        }

        $booking = Booking::findOrFail($bookingId);

        Log::info($booking->id);

        response();
    }
}
