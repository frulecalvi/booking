<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\BookingService;
use App\States\Booking\Paid;
use App\States\Booking\Pending;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateBookingData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Booking $booking;

    /**
     * Create a new job instance.
     */
    public function __construct(
        Booking $booking
    )
    {
        $this->booking = $booking;
    }

    /**
     * Execute the job.
     */
    public function handle(
        BookingService $bookingService
    ): void
    {
        $this->booking->total_price = $bookingService->calculateTotalPrice($this->booking);

        if (count($this->booking->payments)) {
            $this->booking->state = Paid::$name;
        } else {
//            dd($this->booking->payments());
            $this->booking->state = Pending::$name;
        }

        $this->booking->save();
    }
}
