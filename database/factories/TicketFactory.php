<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'person_id' => strval($this->faker->randomNumber(5)),
            'nationality' => $this->faker->word(),
            'quantity' => $this->faker->numberBetween(1, 10)
        ];
    }
}
