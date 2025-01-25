<?php

namespace Database\Factories;

use App\Models\WorkCenter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PartNumber>
 */
class PartNumberFactory extends Factory
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
            'name' => $this->faker->unique()->lexify('??????????'),
            'production_rate' => $this->faker->randomFloat(3, 0, 100),
            'work_center_id' => WorkCenter::inRandomOrder()->first()->id,
            'is_obsolete' => $this->faker->boolean
        ];
    }
}
