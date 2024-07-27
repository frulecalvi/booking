<?php

namespace App\Services;

use App\Models\Booking;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class BookingService
{
    /**
     * @throws \Exception
     */
    public function calculateTotalPrice(Booking $booking)
    {
        $totalPrice = 0;

        foreach ($booking->tickets as $ticket){
            $totalPrice += $ticket->price->amount * $ticket->quantity;
        }

        return roundPrice($totalPrice);
    }

}
