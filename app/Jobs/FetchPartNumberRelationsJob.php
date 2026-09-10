<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class FetchPartNumberRelationsJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $timeout = 300;
    public $backoff = 60;

    protected array $partNumbers;

    public function __construct(array $partNumbers)
    {
        $this->partNumbers = array_values(
            array_unique(
                array_map(fn ($p) => trim(Str::ascii($p)), $partNumbers)
            )
        );
    }

    public function handle(): void
    {
        if (empty($this->partNumbers)) {
            return;
        }

        try {
            $placeholders = implode(',', array_fill(0, count($this->partNumbers), '?'));

            // Se traen TODAS las relaciones (vigentes e históricas, sin filtrar por
            // BDDIS) porque is_active se calcula localmente a partir de
            // BDEFF/BDDIS. Si se filtrara aquí, nunca se detectaría cuándo una
            // relación se descontinúa en Infor.
            $sql = <<<SQL
            SELECT DISTINCT
                TRIM(BPROD) AS parent_part,
                TRIM(BCHLD) AS child_part,
                BSEQ  AS sequence_order,
                BDEFF AS effective_date,
                BDDIS AS discontinue_date
            FROM LX834F01.MBM
            WHERE TRIM(BPROD) IN ($placeholders)
               OR TRIM(BCHLD) IN ($placeholders)
            SQL;

            $params = array_merge($this->partNumbers, $this->partNumbers);
            $results = DB::connection('infor-live')->select($sql, $params);

            // El driver ODBC del AS/400 regresa los nombres de columna en
            // MAYÚSCULAS sin importar cómo se escriba el alias en el SQL (p. ej.
            // "AS parent_part" vuelve como "PARENT_PART"). Como el acceso a
            // propiedades de objeto en PHP es sensible a mayúsculas/minúsculas,
            // se normalizan a minúsculas antes de usarlas.
            $relations = collect($results)
                ->map(fn ($item) => (object) array_change_key_case((array) $item, CASE_LOWER))
                ->filter(function ($item) {
                    $parent = trim((string) ($item->parent_part ?? ''));
                    $child  = trim((string) ($item->child_part ?? ''));
                    return $parent !== '' && $child !== '';
                })
                ->unique(fn ($item) => $item->parent_part . '::' . $item->child_part)
                ->values();

            if ($relations->isEmpty()) {
                return;
            }

            StorePartNumberRelationsJob::dispatch($relations)->onQueue('infor-relations');
        } catch (Throwable $e) {
            $this->handleQueryError($e);
        }
    }

    protected function handleQueryError(Throwable $e): void
    {
        $log = Log::channel('part_number_relations');

        if (
            str_contains($e->getMessage(), 'SQL0802') ||
            str_contains($e->getMessage(), 'SQL0204')
        ) {
            $log->debug('[CONSULTA MBM] Error SQL esperado al sincronizar relaciones de partes en lote', ['error' => $e->getMessage()]);
            return;
        }

        $log->error('[ERROR] Falló la consulta a MBM para este lote', [
            'error' => $e->getMessage(),
            'batch_size' => count($this->partNumbers),
        ]);

        $this->release($this->backoff);
    }

    public function failed(Throwable $exception): void
    {
        Log::channel('part_number_relations')->critical('[FALLO DEFINITIVO] FetchPartNumberRelationsJob agotó sus reintentos', [
            'error' => $exception->getMessage(),
            'batch_size' => count($this->partNumbers),
        ]);
    }
}
