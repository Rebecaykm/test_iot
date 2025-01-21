<?php

namespace Database\Seeders;

use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Shift::create(['abbreviation' => 'D', 'name' => 'Diurno', 'start' => Carbon::createFromTime(8, 0, 0), 'end' => Carbon::createFromTime(20, 0, 0)]);
        Shift::create(['abbreviation' => 'N', 'name' => 'Nocturno', 'start' => Carbon::createFromTime(20, 0, 0), 'end' => Carbon::createFromTime(8, 0, 0)]);
    }
}
