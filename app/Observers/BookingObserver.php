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

        // dd($booking->event->schedule);
        
        $booking->schedule_id = $booking->event->schedule->id;
        $booking->reference_code = $randomString;
        $booking->event_date_time = $booking->event->date_time;
        $booking->bookingable_description = $booking->event->schedule->scheduleable->description;
    }
}
