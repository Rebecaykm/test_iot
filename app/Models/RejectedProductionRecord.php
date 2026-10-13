<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Registro de producción rechazado: JSON que el recolector guarda cuando no pudo
 * registrar en la base de datos lo que leyó del PLC.
 */
class RejectedProductionRecord extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['work_center_id', 'payload'];

    /**
     * El JSON del recolector como arreglo [campo => valor]; vacío si no es un JSON válido.
     */
    public function fields(): array
    {
        $fields = json_decode((string) $this->payload, true);

        return is_array($fields) && ! array_is_list($fields) ? $fields : [];
    }

    /**
     * Fecha y hora del rechazo según el JSON, sin importar mayúsculas en los nombres:
     * "ts" (2026-08-12T17:15:17), o "Fecha" + "Hora" por separado, o solo "fecha".
     * Si el JSON no trae fecha, se usa la de registro.
     */
    public function occurredAt(): Carbon
    {
        $fields = array_change_key_case($this->fields(), CASE_LOWER);

        $value = $fields['ts'] ?? trim(($fields['fecha'] ?? '') . ' ' . ($fields['hora'] ?? ''));

        try {
            return is_string($value) && $value !== '' ? Carbon::parse($value) : $this->created_at;
        } catch (\Throwable) {
            return $this->created_at;
        }
    }
}
