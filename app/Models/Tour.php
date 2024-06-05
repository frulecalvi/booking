<?php

namespace App\Models;

use App\States\Tour\Active;
use App\States\Tour\TourState as TourTourState;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\ModelStates\HasStates;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Tour extends Model
{
    use HasFactory, HasUlids, HasRelationships, HasStates;

    protected $fillable = [
        'name',
        'description',
        'duration',
        'meeting_point',
        'seating',
    ];

    protected $casts = [
        'state' => TourTourState::class
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function bookings()
    {
        return $this->hasManyDeep(Booking::class, [Schedule::class, Event::class], ['scheduleable_id']);
    }

    public function scopeActive($query)
    {
        return $query->whereState('state', Active::class);
    }
}
