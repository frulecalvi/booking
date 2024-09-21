<?php

namespace App\Observers;

use App\Jobs\UpdateBookingData;
use App\Jobs\UpdateTicketData;
use App\Models\Payment;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        $booking = $payment->booking;
        UpdateBookingData::dispatch($booking);
    }
    public function deleted(Payment $payment): void
    {
        $booking = $payment->booking;
//        dd($booking);
        UpdateBookingData::dispatch($booking);
    }
}
