<?php

namespace App\Models;

use App\States\Event\Active;
use App\States\Event\EventState;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\ModelStates\HasStates;

class Event extends Model
{
    use HasFactory, HasUlids, SoftDeletes, HasStates;

    protected $fillable = [
        'date',
        'time',
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

    public function scopeActive($query)
    {
        return $query->whereState('state', Active::class);
    }
}
