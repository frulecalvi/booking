<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\Tour;
use App\States\Booking\Pending;
use App\States\Booking\Expired;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FindExpiredBookings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $bookings = Booking::where([
            ['state', '=', Pending::$name],
            ['created_at', '<=', now()->subMinutes(15)],
        ])->whereHasMorph(
            'bookingable',
            Tour::class,
            function ($queryBuilder) {
                $queryBuilder->where('book_without_payment', '!=', '1');
            }
        )->update(['state' => Expired::$name]);
    }
}
