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
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\ModelStates\HasStates;

#[ScopedBy([TourStateScope::class])]
class Tour extends Model
{
    use HasFactory, HasUlids, HasStates, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'short_description',
        'duration',
        'meeting_point',
        'capacity',
        'minimum_payment_quantity',
        'bookings_impact_availability',
        'book_without_payment',
        'end_date'
    ];

    protected $casts = [
        'state' => TourState::class,
        'bookings_impact_availability' => 'boolean',
        'book_without_payment' => 'boolean',
    ];

    public function schedules(): MorphMany
    {
        return $this->morphMany(Schedule::class, 'scheduleable');
    }

    public function events(): MorphMany
    {
        return $this->morphMany(Event::class, 'eventable');
    }

    public function prices(): MorphMany
    {
        return $this->morphMany(Price::class, 'priceable');
    }

    public function paymentMethods(): MorphToMany
    {
        return $this->morphToMany(PaymentMethod::class, 'payment_methodable');
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
