<?php

namespace Database\Seeders;

use App\Models\ProductionRecord;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductionRecordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProductionRecord::factory(10)->create();
    }
}
