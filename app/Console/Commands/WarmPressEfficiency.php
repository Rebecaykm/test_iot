<?php

namespace App\Console\Commands;

use App\Livewire\Guest\PressEfficiency;
use Illuminate\Console\Command;

class WarmPressEfficiency extends Command
{
    protected $signature = 'iot:warm-press-efficiency';

    protected $description = 'Precalcula en caché la eficiencia de prensas (semana, mes y año en curso) para que la vista abra rápido';

    public function handle(): int
    {
        foreach (PressEfficiency::warm() as $period => $seconds) {
            $this->info("{$period}: {$seconds} s");
        }

        return self::SUCCESS;
    }
}
