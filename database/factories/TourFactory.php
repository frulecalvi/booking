<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tour>
 */
class TourFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence,
            'description' => $this->faker->text(2048),
            'short_description' => $this->faker->text(256),
            'duration' => $this->faker->time(),
            'meeting_point' => $this->faker->address(),
            'end_date' => $this->faker->date(),
            'capacity' => $this->faker->randomNumber(2),
            'minimum_payment_quantity' => $this->faker->randomNumber(2),
            'bookings_impact_availability' => true,
            'book_without_payment' => true,
        ];
    }
}
