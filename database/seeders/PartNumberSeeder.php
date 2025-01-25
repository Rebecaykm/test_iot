<?php

namespace Database\Seeders;

use App\Models\PartNumber;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PartNumberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PartNumber::factory(200)->create();
    }
}
