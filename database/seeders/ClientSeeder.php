<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Client::create(['code' => '200000', 'name' => 'MMVO']);
        Client::create(['code' => '400403', 'name' => 'TOYOTA']);
        Client::create(['code' => '200700', 'name' => 'MNAO']);
        Client::create(['code' => '400501', 'name' => 'F&P']);
    }
}
