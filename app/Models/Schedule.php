<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Schedule extends Model
{
    use HasFactory, HasUlids;

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
}
