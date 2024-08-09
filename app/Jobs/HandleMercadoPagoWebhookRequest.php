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
    protected PaymentMethod $paymentMethod;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $dataId,
        PaymentMethod $paymentMethod
    )
    {
        $this->dataId = $dataId;
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * Execute the job.
     */
    public function handle(MercadoPago $mercadoPago): bool
    {
        $accessToken = $this->paymentMethod->secrets['access_token'];

        $mercadoPago->setConfig($accessToken);

        try {
            $mpPayment = $mercadoPago->getPayment($this->dataId);
        } catch (BadRequestException $e) {
            Log::error("MercadoPago webhook handler error: {$e->getMessage()} - mpPaymentId: {$this->dataId}");
            return false;
        }

        $bookingId = $mpPayment->metadata->booking_id;

        if (
            isset($mpPayment)
            && (! $bookingId || $mpPayment->metadata->payment_method_id !== $this->paymentMethod->id)
        ) {
            Log::error("MercadoPago webhook handler error: payment metadata inconsistency - mpPaymentId: {$this->dataId} - paymentMethodId: {$this->paymentMethod->id}");
            return false;
        }

        if (! $booking = Booking::findOrFail($bookingId)) {
            Log::error("MercadoPago webhook handler error: booking not found - mpPaymentId: {$this->dataId} - bookingId: {$bookingId} - paymentMethodId: {$this->paymentMethod->id}");
            return false;
        }

        $payment = Payment::make();
        $booking->payments()->save($payment);

        return true;
    }
}
