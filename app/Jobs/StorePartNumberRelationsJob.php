<?php

namespace App\Jobs;

use App\Models\PartNumber;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class StorePartNumberRelationsJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 300;

    protected Collection $relations;

    public function __construct(Collection $relations)
    {
        $this->relations = $relations;
    }

    public function handle(): void
    {
        $log = Log::channel('part_number_relations');

        if ($this->relations->isEmpty()) {
            return;
        }

        $now = Carbon::now();
        $today = $now->toDateString();

        // Recopilar todos los números de parte únicos del lote en una sola query.
        $allNumbers = $this->relations
            ->flatMap(fn ($r) => [
                trim((string) ($r->parent_part ?? '')),
                trim((string) ($r->child_part ?? '')),
            ])
            ->filter(fn ($n) => $n !== '')
            ->unique()
            ->values()
            ->all();

        $partMap = PartNumber::whereIn('number', $allNumbers)
            ->select('id', 'number')
            ->get()
            ->keyBy('number');

        $rows = [];
        $skipped = 0;

        foreach ($this->relations as $relation) {
            $parentNumber = trim((string) ($relation->parent_part ?? ''));
            $childNumber  = trim((string) ($relation->child_part ?? ''));

            $parentPart = $partMap->get($parentNumber);
            $childPart  = $partMap->get($childNumber);

            if (!$parentPart || !$childPart || $parentPart->id === $childPart->id) {
                $skipped++;
                continue;
            }

            $effectiveDate   = $this->parseInforDate($relation->effective_date ?? null);
            $discontinueDate = $this->parseInforDate($relation->discontinue_date ?? null);

            $isActive = (!$effectiveDate || $effectiveDate->toDateString() <= $today)
                && (!$discontinueDate || $discontinueDate->toDateString() >= $today);

            $rows[] = [
                'parent_part_number_id' => $parentPart->id,
                'child_part_number_id'  => $childPart->id,
                'sequence_order'        => (int) ($relation->sequence_order ?? 0) ?: null,
                'effective_date'        => $effectiveDate,
                'discontinue_date'      => $discontinueDate,
                'is_active'             => $isActive,
                'last_synced_at'        => $now,
                'created_at'            => $now,
                'updated_at'            => $now,
            ];
        }

        if ($skipped > 0) {
            $log->warning("[GUARDAR] {$skipped} relación(es) omitida(s): número de parte no encontrado localmente o auto-relación");
        }

        if (empty($rows)) {
            return;
        }

        // Se escribe en lotes pequeños, no en un solo upsert gigante, para
        // mantener cada consulta ligera y no retener la conexión mucho tiempo.
        // El tamaño también respeta el límite de SQL Server de 2100 parámetros
        // por sentencia: cada fila manda 9 columnas, así que 200 filas = 1800
        // parámetros, con margen de sobra.
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('part_number_relations')->upsert(
                $chunk,
                ['parent_part_number_id', 'child_part_number_id'],
                ['sequence_order', 'effective_date', 'discontinue_date', 'is_active', 'last_synced_at', 'updated_at']
            );
        }
    }

    /**
     * Convierte una fecha de Infor (YYYYMMDD) a Carbon. Los centinelas
     * '99999999' (sin fecha de término) y '00000000' (sin fecha capturada) se
     * traducen a null.
     */
    protected function parseInforDate($value): ?Carbon
    {
        $value = trim((string) $value);

        if ($value === '' || $value === '0' || $value === '99999999' || $value === '00000000') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Ymd', $value)->startOfDay();
        } catch (Throwable $e) {
            return null;
        }
    }
}
