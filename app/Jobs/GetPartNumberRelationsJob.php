<?php

namespace App\Jobs;

use App\Models\PartNumber;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GetPartNumberRelationsJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 3600;

    /**
     * Tamaño de lote consultado a Infor por corrida. Se mantiene bajo para no
     * mandar listas IN() enormes contra el AS/400 vía ODBC.
     */
    protected const CHUNK_SIZE = 150;

    /**
     * Segundos de separación entre cada despacho de FetchPartNumberRelationsJob,
     * para no abrir muchas conexiones a Infor al mismo tiempo.
     */
    protected const DISPATCH_DELAY_SECONDS = 5;

    protected const QUEUE = 'infor-relations';

    /**
     * Nombre del candado que evita corridas simultáneas. Público para que el
     * comando pueda forzar su liberación (--force) si quedó pegado por un
     * error que no permitió llegar al finally (p. ej. un fatal error de PHP).
     */
    public const LOCK_KEY = 'get-part-number-relations';

    public function handle(): void
    {
        $log = Log::channel('part_number_relations');

        // Evita que dos corridas de esta sincronización se traslapen.
        $lock = Cache::lock(self::LOCK_KEY, $this->timeout + 60);

        if (!$lock->get()) {
            $log->warning('[OMITIDO] Ya hay una sincronización de relaciones en curso, se omite esta ejecución');
            return;
        }

        try {
            // No se filtra por is_obsolete: esa bandera indica si la parte se
            // sigue produciendo hoy, no si sigue siendo válida dentro de la
            // estructura histórica del BOM (que es justo lo que esta
            // sincronización reconstruye). La vigencia real de cada relación ya
            // la determina is_active, calculado a partir de BDEFF/BDDIS.
            $baseQuery = fn () => PartNumber::query()
                ->whereNotExists(function ($query) {
                    // Excluir partes cuyas relaciones (como padre o como hijo) ya se
                    // sincronizaron en las últimas 20 horas.
                    $query->select(DB::raw(1))
                        ->from('part_number_relations')
                        ->where(function ($q) {
                            $q->whereColumn('parent_part_number_id', 'part_numbers.id')
                                ->orWhereColumn('child_part_number_id', 'part_numbers.id');
                        })
                        ->where('last_synced_at', '>=', now()->subHours(20));
                });

            if ($baseQuery()->count() === 0) {
                return;
            }

            // Con una cola real (database/redis) ->delay() sí funciona y no
            // bloquea al worker. Con QUEUE_CONNECTION=sync (solo en local) Laravel
            // ignora ->delay() por completo y el job corre inline de inmediato, así
            // que ahí se sustituye por una pausa real con sleep().
            $isSyncQueue = config('queue.default') === 'sync';
            $delaySeconds = 0;

            $baseQuery()
                ->select('id', 'number')
                ->orderBy('id')
                ->chunkById(self::CHUNK_SIZE, function ($partNumbers) use (&$delaySeconds, $isSyncQueue) {
                    $numbers = $partNumbers->pluck('number')->all();

                    if ($isSyncQueue) {
                        FetchPartNumberRelationsJob::dispatch($numbers)->onQueue(self::QUEUE);
                        sleep(self::DISPATCH_DELAY_SECONDS);
                    } else {
                        FetchPartNumberRelationsJob::dispatch($numbers)
                            ->onQueue(self::QUEUE)
                            ->delay(now()->addSeconds($delaySeconds));
                        $delaySeconds += self::DISPATCH_DELAY_SECONDS;
                    }
                });
        } finally {
            $lock->release();
        }
    }
}
