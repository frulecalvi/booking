<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Schedule>
 */
class ScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'period' => $this->faker->randomElement(['once', 'weekly']),
            'day' => $this->faker->numberBetween(1, 7),
            'time' => $this->faker->time(),
            'date' => $this->faker->date(),
        ];
    }
}
