<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Znck\Eloquent\Relations\BelongsToThrough;
use Znck\Eloquent\Traits\BelongsToThrough as BelongsToThroughTrait;

class Booking extends Model
{
    use HasFactory, HasUlids, HasRelationships, SoftDeletes, BelongsToThroughTrait;

    protected $fillable = [
        'reference_code',
        'contact_name',
        'contact_email'
        // 'event_id'
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function scheduleable()
    {
        return $this->hasOneDeepFromReverse(
            ($this->schedule->scheduleable)->bookings()
        );
    }
}
