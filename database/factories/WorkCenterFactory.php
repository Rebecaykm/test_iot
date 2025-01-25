<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkCenter>
 */
class WorkCenterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' =>  $this->faker->unique()->numerify('######'),
            'name' => $this->faker->unique()->word,
            'ip' => $this->faker->ipv4
        ];
    }
}
