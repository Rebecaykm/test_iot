<?php

namespace Database\Factories;

use App\Models\PartNumber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\History>
 */
class HistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'part_number_id' => PartNumber::inRandomOrder()->first()->id,
            'quantity' => $this->faker->numberBetween(1, 1000),
            'sequence' => $this->faker->numberBetween(1, 1000)
        ];
    }
}
