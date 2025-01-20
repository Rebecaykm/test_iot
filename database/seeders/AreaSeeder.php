<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Area::create(['code' => '20', 'name' => 'Mazda']);
        Area::create(['code' => '11', 'name' => 'Estampado']);
        Area::create(['code' => '12', 'name' => 'Carrocería']);
        Area::create(['code' => '13', 'name' => 'Chasis']);
        Area::create(['code' => '14', 'name' => 'Pintura']);
        Area::create(['code' => '40', 'name' => 'Proveedor']);
    }
}
