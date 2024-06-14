<?php

namespace App\Models;

use App\Models\Scopes\TourStateScope;
use App\States\Tour\Active;
use App\States\Tour\TourState;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\ModelStates\HasStates;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

#[ScopedBy([TourStateScope::class])]
class Tour extends Model
{
    use HasFactory, HasUlids, HasRelationships, HasStates, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'duration',
        'meeting_point',
        'capacity',
        'end_date'
    ];

    protected $casts = [
        'state' => TourState::class
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    // public function events(): HasManyThrough
    // {
    //     return $this->hasManyThrough(Event::class, Schedule::class, 'scheduleable_id')
    //         ->where(
    //             'scheduleable_type', 
    //             array_search(static::class, Relation::morphMap()) ?: static::class
    //         );
    // }

    public function events(): MorphMany
    {
        return $this->morphMany(Event::class, 'eventable');
    }

    public function bookings()
    {
        return $this->hasManyDeep(Booking::class, [Schedule::class, Event::class], ['scheduleable_id']);
    }

    public function prices(): MorphMany
    {
        return $this->morphMany(Price::class, 'priceable');
    }

    public function tourCategory(): BelongsTo
    {
        return $this->belongsTo(TourCategory::class);
    }

    public function scopeActive($query)
    {
        return $query->whereState('state', Active::class);
    }

    public function availableDates(): array
    {
        $availableDates = array_map(fn($dateTime) => Carbon::parse($dateTime)->format('Y-m-d'), $this->events->pluck('date_time')->toArray());
        sort($availableDates);

        return array_merge(array_unique($availableDates), []);
    }
}
