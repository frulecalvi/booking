<?php

namespace App\Models;

use App\Models\Scopes\ScheduleStateScope;
use App\States\Schedule\Active;
use App\States\Schedule\ScheduleState;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\ModelStates\HasStates;

#[ScopedBy([ScheduleStateScope::class])]
class Schedule extends Model
{
    use HasFactory, HasUlids, HasStates, SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['events'];

    protected $fillable = [
        'state',
        'period',
        'day',
        'date',
        'time',
    ];

    protected $casts = [
        'state' => ScheduleState::class,
        'date' => 'datetime:Y-m-d',
        'time' => 'datetime:H:i:s',
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
