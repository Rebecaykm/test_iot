<?php

namespace App\Console\Commands;

use App\Jobs\GetPartNumberRelationsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class GetPartNumberRelations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'iot:part-number-relations {--force : Libera cualquier bloqueo de sincronización pegado antes de ejecutar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Obtiene de Infor la estructura padre-hijo (BOM) de los números de parte y la guarda en la base de datos del sistema.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('force')) {
            Cache::lock(GetPartNumberRelationsJob::LOCK_KEY)->forceRelease();
            $this->warn('Se forzó la liberación de cualquier bloqueo de sincronización previo.');
        }

        $this->info('Sincronizando relaciones de números de parte...');

        GetPartNumberRelationsJob::dispatch();

        $this->info('Sincronización finalizada.');
    }
}
