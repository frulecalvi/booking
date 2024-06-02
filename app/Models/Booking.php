<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Znck\Eloquent\Relations\BelongsToThrough;
// use Znck\Eloquent\Traits\BelongsToThrough as BelongsToThroughTrait;

class Booking extends Model
{
    use HasFactory, HasRelationships;

    protected $fillable = [
        'reference_code'
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function schedule(): BelongsToThrough
    {
        return $this->belongsToThrough(Schedule::class, Event::class);
    }

    public function scheduleable()
    {
        return $this->hasOneDeepFromReverse(
            ($this->schedule->scheduleable)->bookings()
        );
    }
}
