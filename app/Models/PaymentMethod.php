<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Crypt;

class PaymentMethod extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'payment_method_type',
        'secrets'
    ];

    protected $casts = [
        'secrets' => 'encrypted:array'
    ];

//    protected $hidden = [
//        'name',
//    ];

//    protected function credentials(): Attribute
//    {
//        return Attribute::make(
//            get: function (string $credentials) {
//                $credentials = json_decode($credentials, 1);
//                return array_map(fn($value) => Crypt::decryptString($value), $credentials);
//            }
//        );
//    }

    public function tours(): MorphToMany
    {
        return $this->morphedByMany(Tour::class, 'payment_methodable');
    }
}
