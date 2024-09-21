<?php

namespace App\Observers;

use App\Jobs\UpdateBookingData;
use App\Jobs\UpdateTicketData;
use App\Models\Ticket;

class TicketObserver
{
    public function created(Ticket $ticket): void
    {
        $booking = $ticket->booking;
        UpdateTicketData::dispatch($ticket);
        UpdateBookingData::dispatch($booking);
    }
    public function updated(Ticket $ticket): void
    {
        $booking = $ticket->booking;
        UpdateTicketData::dispatch($ticket);
        UpdateBookingData::dispatch($booking);
    }
    public function deleted(Ticket $ticket): void
    {
        $booking = $ticket->booking;
        UpdateBookingData::dispatch($booking);
    }
}
