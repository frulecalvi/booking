<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Price extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'amount',
        'currency',
        'capacity',
    ];

    public function priceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getAmountAttribute($value) {
        return formatPriceAsString($value);
    }
}
