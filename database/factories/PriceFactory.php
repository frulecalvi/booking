<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Price>
 */
class PriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'amount' => strval($this->faker->randomFloat(2, 0, 4000000.00)),
            'currency' => $this->faker->currencyCode(),
            'capacity' => $this->faker->numberBetween(0, 50),
        ];
    }
}
