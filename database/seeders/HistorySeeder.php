<?php

namespace Database\Seeders;

use App\Models\History;
use App\Models\PartNumber;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seleccionar 5 part_numbers aleatorios
        $partNumbers = PartNumber::inRandomOrder()->take(5)->get();

        // Definir la hora de inicio
        $startTime = Carbon::createFromTime(6, 0); // 06:00 AM

        // Número total de registros
        $totalRecords = 100;

        // Calcular registros por part_number
        $recordsPerPartNumber = $totalRecords / $partNumbers->count(); // 10 por cada part_number

        foreach ($partNumbers as $partNumber) {
            // Cantidad inicial
            $quantity = rand(1, 1000);

            for ($i = 0; $i < $recordsPerPartNumber; $i++) {
                History::create([
                    'part_number_id' => $partNumber->id,
                    'quantity' => $quantity,
                    'sequence' => $i + 1,
                    'created_at' => $startTime->toDateTimeString(),
                    'updated_at' => $startTime->toDateTimeString(),
                ]);

                // Incrementar la cantidad en 10
                $quantity += 10;

                // Incrementar la hora en 30 minutos
                $startTime->addMinutes(30);
            }

            // Reiniciar la hora de inicio para el próximo `part_number`
            $startTime = Carbon::createFromTime(6, 0);
        }
    }
}
