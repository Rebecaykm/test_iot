<?php

namespace App\Console\Commands;

use App\Jobs\GetPartNumberJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class GetPartNumber extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'iot:part-number {--force : Libera cualquier bloqueo de sincronización pegado antes de ejecutar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Obtain the part numbers registered in Infor to register them in the system\'s database.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('force')) {
            Cache::lock(GetPartNumberJob::LOCK_KEY)->forceRelease();
            $this->warn('Se forzó la liberación de cualquier bloqueo de sincronización previo.');
        }

        info("Process GetPartNumberJob is running at ". now());

        GetPartNumberJob::dispatch();
    }
}
