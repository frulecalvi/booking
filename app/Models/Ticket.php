<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Ticket extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'person_id',
        'nationality',
        'quantity',
    ];

    // public function ticketable(): MorphTo
    // {
    //     return $this->morphTo();
    // }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function price(): BelongsTo
    {
        return $this->belongsTo(Price::class);
    }
}
