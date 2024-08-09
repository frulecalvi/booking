<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\MercadoPago;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class HandleMercadoPagoWebhookRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $xRequestId;
    protected string $xSignature;
    protected string $dataId;
    protected string $paymentMethodId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $xRequestId,
        string $xSignature,
        string $dataId,
        string $paymentMethodId
    )
    {
        $this->xRequestId = $xRequestId;
        $this->xSignature = $xSignature;
        $this->dataId = $dataId;
        $this->paymentMethodId = $paymentMethodId;
    }

    /**
     * Execute the job.
     */
    public function handle(MercadoPago $mercadoPago): bool
    {
        if (! $paymentMethod = PaymentMethod::find($this->paymentMethodId)) {
            Log::error("MercadoPago webhook handler error: payment method not found - mpPaymentId: {$this->dataId} - paymentMethodId: {$this->paymentMethodId}");
            return false;
        }

        $webhookSecret = $paymentMethod->secrets['webhook_secret'];
        $accessToken = $paymentMethod->secrets['access_token'];

        if (! $webhookSecret || ! $accessToken) {
            Log::error("MercadoPago webhook handler error: secrets not set - mpPaymentId: {$this->dataId} - paymentMethodId: {$this->paymentMethod->id}");
            return false;
        }

        try {
            $webhookValidation = $mercadoPago->validateWebhookNotification(
                webhookSecret: $webhookSecret,
                xRequestId: $this->xRequestId,
                xSignature: $this->xSignature,
                dataId: $this->dataId,
            );
        } catch (BadRequestException $e) {
            Log::error("MercadoPago webhook handler error: validation error - mpPaymentId: {$this->dataId} - paymentMethodId: {$paymentMethod->id}");
            return false;
        }

        $mercadoPago->setConfig($accessToken);

        try {
            $mpPayment = $mercadoPago->getPayment($this->dataId);
        } catch (BadRequestException $e) {
            Log::error("MercadoPago webhook handler error: {$e->getMessage()} - mpPaymentId: {$this->dataId}");
            return false;
        }

        $bookingId = $mpPayment['metadata']['booking_id'];

        if (
            isset($mpPayment)
            && (! $bookingId || $mpPayment['metadata']['payment_method_id'] !== $paymentMethod->id)
        ) {
            Log::error("MercadoPago webhook handler error: payment metadata inconsistency - mpPaymentId: {$this->dataId} - paymentMethodId: {$paymentMethod->id}");
            return false;
        }

        if (! $booking = Booking::findOrFail($bookingId)) {
            Log::error("MercadoPago webhook handler error: booking not found - mpPaymentId: {$this->dataId} - bookingId: {$bookingId} - paymentMethodId: {$paymentMethod->id}");
            return false;
        }

        $payment = Payment::make();
        $booking->payments()->save($payment);

        return true;
    }
}
