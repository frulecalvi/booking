<?php

namespace Tests\Feature;

use App\Models\Price;
use App\Models\Tour;
use App\States\Tour\Active as TourActive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TourPriceTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_fetching_a_tour_related_prices_is_allowed_for_anonymous_users()
    {
        // $this->withoutExceptionHandling();

        $tour = Tour::factory()->create([
            'state' => TourActive::$name,
            'end_date' => now()->addYear(),
        ]);

        $prices = Price::factory(4)
            ->for($tour, 'priceable')
            ->create();

        $expected = [];

        foreach ($prices as $price) {
            $expected[] = [
                'type' => 'prices',
                'id' => $price->id,
                'attributes' => [
                    'amount' => strval(number_format($price->amount, 2, '.', '')),
                    'capacity' => $price->capacity,
                    'currency' => $price->currency,
                    'description' => $price->description,
                    'title' => $price->title,
                ]
            ];
        }

        $response = $this
            ->jsonApi()
            ->expects('prices')
            ->get(route('v1.tours.prices', $tour->getRouteKey()));

        $response->assertFetchedMany($expected);
    }
}
