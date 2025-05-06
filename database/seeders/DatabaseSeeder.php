<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ClientSeeder::class,
            ProjectSeeder::class,
            AreaSeeder::class,
            ShiftSeeder::class,
            StatusSeeder::class,
            WorkCenterSeeder::class,
            PartNumberSeeder::class,
            ProductionRecordSeeder::class,
            HistorySeeder::class
        ]);
    }
}
