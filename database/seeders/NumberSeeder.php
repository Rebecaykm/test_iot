<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NumberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('numbers')->delete();

        $data = collect(range(1, 1000))->map(function ($number) {
            return [
                'number' => $number,
            ];
        })->toArray();

        DB::table('numbers')->insert($data);
    }
}
