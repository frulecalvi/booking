<?php

namespace App\Models;

use App\States\Schedule\Active;
use App\States\Schedule\ScheduleState;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\ModelStates\HasStates;

class Schedule extends Model
{
    use HasFactory, HasUlids, HasStates;

    protected $fillable = [
        'state',
        'period',
        'day',
        'end_date',
        'time',
    ];

    protected $casts = [
        'state' => ScheduleState::class
    ];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    // public function bookings(): HasManyThrough
    // {
    //     return $this->hasManyThrough(Booking::class, Event::class);
    // }

    public function scheduleable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeActive($query)
    {
        return $query->whereState('state', Active::class);
    }
}
