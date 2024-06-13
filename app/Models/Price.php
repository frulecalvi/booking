<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Price extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'title',
        'description',
        'amount',
        'currency',
    ];

    public function priceable(): MorphTo
    {
        return $this->morphTo();
    }
}
