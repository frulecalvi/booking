<?php

namespace App\Models;

use App\States\Booking\BookingState;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\ModelStates\HasStates;

class Booking extends Model
{
    use HasFactory, HasUlids, SoftDeletes, HasStates;

    protected $fillable = [
        'reference_code',
//        'contact_name',
        'contact_email',
        'contact_phone_number'
        // 'event_id'
    ];

    protected $casts = [
        'state' => BookingState::class
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function bookingable(): MorphTo
    {
        return $this->morphTo();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getTotalPriceAttribute($value) {
        return formatPriceAsString($value);
    }
}
