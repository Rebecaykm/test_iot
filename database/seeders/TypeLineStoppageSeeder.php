<?php

namespace Database\Seeders;

use App\Models\TypeLineStoppage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TypeLineStoppageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TypeLineStoppage::create([
            'name' => 'Planeado',
            'description' => 'Paradas programadas y planificadas'
        ]);

        TypeLineStoppage::create([
            'name' => 'Normales',
            'description' => 'Paradas operativas normales del proceso'
        ]);

        TypeLineStoppage::create([
            'name' => 'Anormales',
            'description' => 'Paradas no planificadas e inesperadas'
        ]);
    }
}
