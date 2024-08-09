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

        if (! $xRequestId || ! $xSignature || ! $dataId) {
            Log::warning("MercadoPago webhook call failed: received data is incomplete - paymentId: {$dataId} - xRequestId: {$xRequestId} - xSignature: {$xSignature}");
            abort(400);
        }

        HandleMercadoPagoWebhookRequest::dispatch(
            $xRequestId,
            $xSignature,
            $dataId,
            $request->query('paymentMethodId')
        );

        return response(200);
    }
}
