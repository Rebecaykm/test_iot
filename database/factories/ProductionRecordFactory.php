<?php

namespace Database\Factories;

use App\Models\PartNumber;
use App\Models\Shift;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductionRecord>
 */
class ProductionRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $plannedDate = $this->faker->dateTimeBetween($startOfWeek, $endOfWeek);

        $randomHour = $this->faker->numberBetween(0, 23);
        $productionStart = Carbon::parse($plannedDate)->addHours($randomHour);
        $productionEnd = Carbon::parse($productionStart)->addHours(6);

        $plannedQuantity = $this->faker->numberBetween(50, 1000);
        $producedQuantity = $this->faker->numberBetween(0, $plannedQuantity);

        $statusIds = Status::whereIn('name', ['Pendiente', 'No planeado', 'Completado'])
            ->pluck('id')
            ->toArray();

        return [
            'part_number_id' => PartNumber::inRandomOrder()->first()->id,
            'planned_quantity' => $plannedQuantity,
            'produced_quantity' => $producedQuantity,
            'planned_date' => Carbon::parse($plannedDate)->toDateString(),
            'production_start' => $productionStart->toDateTimeString(),
            'production_end' => $productionEnd->toDateTimeString(),
            'shift_id' => Shift::inRandomOrder()->first()->id,
            'status_id' => $this->faker->randomElement($statusIds),
        ];
    }
}
