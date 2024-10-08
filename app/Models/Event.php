<?php

namespace App\Models;

use App\Models\Scopes\EventDateScope;
use App\Models\Scopes\EventStateScope;
use App\States\Event\Active;
use App\States\Event\EventState;
use DateTime;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\ModelStates\HasStates;

#[ScopedBy([EventStateScope::class])]
#[ScopedBy([EventDateScope::class])]
class Event extends Model
{
    use HasFactory, HasUlids, SoftDeletes, HasStates;

    protected $fillable = [
        'date_time'
    ];

    protected $casts = [
        'state' => EventState::class
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function eventable(): MorphTo
    {
        return $this->morphTo();
    }

    // public function prices(): HasManyThrough
    // {
    //     return $this->hasMany(Price::class, get_class($this->eventable()->getRelated()))
    //         ->where(
    //             'eventable_type', 
    //             array_search(static::class, Relation::morphMap()) ?: static::class
    //         );
    // }

    public function tickets(): HasManyThrough
    {
        return $this->hasManyThrough(Ticket::class, Booking::class);
    }

    public function scopeActive($query)
    {
        return $query->whereState($this->qualifyColumn('state'), Active::class);
    }

    public function scopeFuture($query)
    {
        return $query->where($this->qualifyColumn('date_time'), '>', now()->addHour());
    }

    public function scopeClose($query)
    {
        return $query->where($this->qualifyColumn('date_time'), '<=', now()->addDays(30));
    }

    public function availability(): array
    {
        $totalAvailability = $this->eventable->capacity;

        $productPrices = $this->eventable->prices;

        $pricesAvailability = [];

        foreach ($productPrices as $price) {
            $pricesAvailability[$price->id] = $price->capacity;
        }

        if ($this->eventable->bookings_impact_availability) {

            $totalBookings = 0;

            foreach ($productPrices as $price) {
                $priceTickets = $this->tickets->where('price_id', '=', $price->id);

                $priceBookings = 0;
                foreach ($priceTickets as $currentTicket) {
                    $priceBookings += $currentTicket->quantity;
                    $totalBookings += $currentTicket->quantity;
                }

                $pricesAvailability[$price->id] = $price->capacity === 0 ?
                    0
                    : $price->capacity - $priceBookings;
            }

            $totalAvailability -= $totalBookings;
        }

        foreach ($pricesAvailability as $priceId => $quantity) {
            if ($quantity > $totalAvailability)
                $pricesAvailability[$priceId] = $totalAvailability;
        }

        return [
            'total' => $totalAvailability,
            'prices' => $pricesAvailability
        ];
    }
}
