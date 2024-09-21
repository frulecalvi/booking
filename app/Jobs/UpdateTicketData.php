<?php

namespace App\Jobs;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateTicketData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Ticket $ticket;

    /**
     * Create a new job instance.
     */
    public function __construct(
        Ticket $ticket
    )
    {
        $this->ticket = $ticket;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->ticket->price->amount != $this->ticket->price_amount) {
            $this->ticket->price_amount = $this->ticket->price->amount;
            $this->ticket->save();
        }
    }
}
