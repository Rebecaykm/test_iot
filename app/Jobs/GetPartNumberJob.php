<?php

namespace App\Jobs;

use App\Models\IIM;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GetPartNumberJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 3600;

    /**
     * Tamaño de lote para agrupar la consulta de ruteo (FRT/LWK) contra
     * Infor. Antes se hacía una consulta por cada número de parte (10,000+
     * conexiones ODBC por corrida); agrupando en lotes con whereIn baja a
     * decenas de consultas, lo que hace viable correr esto a diario.
     */
    protected const CHUNK_SIZE = 200;

    protected const QUEUE = 'infor-part-numbers';

    /**
     * Nombre del candado que evita corridas simultáneas. Público para que el
     * comando pueda forzar su liberación (--force) si quedó pegado por un
     * error que no permitió llegar al finally.
     */
    public const LOCK_KEY = 'get-part-number';

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $lock = Cache::lock(self::LOCK_KEY, $this->timeout + 60);

        if (! $lock->get()) {
            Log::warning('[GetPartNumberJob] Ya hay una sincronización de números de parte en curso, se omite esta ejecución');
            return;
        }

        try {
            $facility = 'YK1';

            $itemMaster = IIM::query()
                ->select('IPROD AS partNumber', 'IDESC AS partName', 'ICLAS AS class', 'IREF04 AS project', 'IMPLC AS isObsolete', 'IMSPKT AS standardPack', 'IMBOXQ AS quantityStandardPack')
                ->get()
                ->map(fn ($item) => [
                    'partNumber' => preg_replace('/[^a-zA-Z0-9\/\-\s()]/', '', trim($item->partNumber)),
                    'partName' => preg_replace('/[^a-zA-Z0-9\/\-\s()]/', '', trim($item->partName)),
                    'class' => trim($item->class),
                    'project' => $item->project,
                    'isObsolete' => $item->isObsolete,
                    'standardPack' => trim($item->standardPack),
                    'quantityStandardPack' => trim($item->quantityStandardPack),
                ]);

            $itemMaster->chunk(self::CHUNK_SIZE)->each(function ($chunk) use ($facility) {
                $partNumbers = $chunk->pluck('partNumber')->all();

                $routingMasters = DB::connection('infor-live')
                    ->table('LX834F01.FRT')
                    ->select([
                        'LX834F01.IIM.IPROD AS partNumber',
                        'LX834F01.LWK.WWRKC AS workNumber',
                        'LX834F01.LWK.WDESC AS workName',
                        'LX834F01.FRT.RLAB AS productionRate',
                        'LX834F01.FRT.RTWHS AS facility',
                    ])
                    ->join('LX834F01.IIM', 'LX834F01.IIM.IPROD', '=', 'LX834F01.FRT.RPROD')
                    ->join('LX834F01.LWK', 'LX834F01.LWK.WWRKC', '=', 'LX834F01.FRT.RWRKC')
                    ->whereIn('LX834F01.IIM.IPROD', $partNumbers)
                    ->where('LX834F01.FRT.RTWHS', '=', $facility)
                    ->get()
                    ->keyBy(fn ($row) => trim($row->partNumber));

                foreach ($chunk as $item) {
                    StorePartNumberJob::dispatch(
                        $item['partNumber'],
                        $item['partName'],
                        $item['class'],
                        $item['project'],
                        $item['isObsolete'],
                        $item['standardPack'],
                        $item['quantityStandardPack'],
                        $routingMasters->get($item['partNumber'])
                    )->onQueue(self::QUEUE);
                }
            });
        } finally {
            $lock->release();
        }
    }
}
