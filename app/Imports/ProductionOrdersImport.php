<?php

namespace App\Imports;

use App\Models\PartNumber;
use App\Models\ProductionOrderHistory;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductionOrdersImport implements ToCollection, WithHeadingRow
{
    public int $updated = 0;

    /** Números de parte del archivo que no existen (o están obsoletos) */
    public array $notFound = [];

    /** Filas del archivo (número de fila real) con datos vacíos o inválidos */
    public array $invalidRows = [];

    /** false cuando el archivo no trae los encabezados NO PARTE y ORDEN */
    public bool $headersValid = true;

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            $this->headersValid = false;
            return;
        }

        // Los encabezados llegan como slugs ("NO PARTE" => no_parte, "ORDEN" => orden).
        // Se buscan por contenido para tolerar variantes como "NO. PARTE" o "No de Parte".
        $keys = collect($rows->first()->keys());
        $parteKey = $keys->first(fn ($key) => str_contains((string) $key, 'parte'));
        $ordenKey = $keys->first(fn ($key) => str_contains((string) $key, 'orden'));

        if ($parteKey === null || $ordenKey === null) {
            $this->headersValid = false;
            return;
        }

        foreach ($rows as $index => $row) {
            $number = trim((string) ($row[$parteKey] ?? ''));
            $orden = $row[$ordenKey] ?? null;

            // Fila completamente vacía: ignorar sin marcar error
            if ($number === '' && ($orden === null || trim((string) $orden) === '')) {
                continue;
            }

            if ($number === '' || $orden === null || !is_numeric($orden)) {
                // +2: los índices inician en 0 y la fila 1 es el encabezado
                $this->invalidRows[] = $index + 2;
                continue;
            }

            $partNumber = PartNumber::query()
                ->where('number', $number)
                ->where('is_obsolete', '<>', 1)
                ->first();

            if ($partNumber === null) {
                $this->notFound[] = $number;
                continue;
            }

            $newOrder = (int) $orden;

            if ($partNumber->production_order !== $newOrder) {
                ProductionOrderHistory::create([
                    'part_number_id' => $partNumber->id,
                    'work_center_id' => $partNumber->work_center_id,
                    'production_order' => $newOrder,
                    'effective_date' => now()->toDateString(),
                ]);

                $partNumber->update(['production_order' => $newOrder]);
            }

            $this->updated++;
        }
    }
}
