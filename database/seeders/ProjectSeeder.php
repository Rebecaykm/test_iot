<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clientIds = [
            '200000' => Client::where('code', '200000')->value('id'),
            '200700' => Client::where('code', '200700')->value('id'),
            '400403' => Client::where('code', '400403')->value('id'),
            '400501' => Client::where('code', '400501')->value('id')
        ];

        $projects = [
            ['type' => '1', 'model' => 'J36W', 'prefix' => 'BJS - BPN - BRB - BAC- PED - BJV', 'client_code' => '200000'],
            ['type' => '2', 'model' => 'J03W', 'prefix' => 'DA6 - DA7 - DA8 - P54 - DB1 - DD1 - DG7 - S51 - DGN', 'client_code' => '200000'],
            ['type' => '3', 'model' => 'J03G', 'prefix' => 'DB7 - DD1 - DB7', 'client_code' => '200000'],
            ['type' => '4', 'model' => 'J59W', 'prefix' => 'BDTS - BDTT - BDW - BDTV - BEK - BDWP - BDYS - BGV - BHY - BJE-BJD- PX1 - PEP ', 'client_code' => '200000'],
            ['type' => '5', 'model' => 'J59J', 'prefix' => 'DGH - DGJ - DGK - DGL - DGY - DRV- PYY', 'client_code' => '200700'],
            ['type' => '7', 'model' => 'J34A', 'prefix' => 'VA40 - BDTS70234 - BDTS56A9X', 'client_code' => '400403'],
            ['type' => '8', 'model' => '660B', 'prefix' => '575 - 576 - 582 - 583', 'client_code' => '200700'],
            ['type' => '9', 'model' => 'J34H', 'prefix' => 'VC67', 'client_code' => '200700'],
            ['type' => '10', 'model' => 'J34X', 'prefix' => 'VC85', 'client_code' => '400403'],
            ['type' => '11', 'model' => '920B', 'prefix' => '573 - 520', 'client_code' => '400403'],
            ['type' => '3Y', 'model' => '3Y', 'prefix' => '104 (FG)', 'client_code' => '400501'],
            ['type' => '20', 'model' => 'J03N', 'prefix' => 'DNJ - DHM - DDD - DA6 - PED', 'client_code' => '200000']
        ];

        foreach ($projects as $projectData) {
            $project = Project::create([
                'type' => $projectData['type'],
                'model' => $projectData['model'],
                'prefix' => $projectData['prefix']
            ]);

            $project->update([
                'client_id' => $clientIds[$projectData['client_code']]
            ]);
        }
    }
}
