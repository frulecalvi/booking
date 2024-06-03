<?php

namespace App\Observers;

use App\Models\Booking;

class BookingObserver
{
    public function creating(Booking $booking)
    {
        $length = 10;
        
        $existingBooking = 'maybe';

        while ($existingBooking !== null) {
            $randomString = strtoupper(
                substr(
                    bin2hex(random_bytes(ceil($length / 2))),
                    0,
                    $length
                )
            );

            $existingBooking = Booking::where(['reference_code' => $randomString])->first();
        }
        
        $booking->reference_code = $randomString;
        $booking->event_date = $booking->event->date;
        $booking->event_time = $booking->event->time;
        $booking->scheduleable_description = $booking->event->schedule->scheduleable->description;
    }
}
