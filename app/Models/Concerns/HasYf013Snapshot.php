<?php

namespace App\Models\Concerns;

trait HasYf013Snapshot
{
    /**
     * Líneas formateadas de todos los registros actuales, para log de snapshot.
     */
    public static function snapshotLines(): array
    {
        return static::query()->get()->map(function ($row, $index) {
            return sprintf(
                '[%d] WorkCenter: %s (%s) | Orden: %s | Parte: %s | Fecha: %s | Turno: %s | Inicio: %s | Fin: %s | Plan: %s | Prod: %s | Scrap: %s | Creado: %s %s por %s',
                $index + 1,
                trim($row->YFWRKC ?? ''),
                trim($row->YFWRKN ?? ''),
                trim($row->YFSORD ?? ''),
                trim($row->YFPROD ?? ''),
                trim($row->YFRDTE ?? ''),
                trim($row->YFSHFT ?? ''),
                trim($row->YFSTIM ?? ''),
                trim($row->YFETIM ?? ''),
                $row->YFQPLA ?? '',
                $row->YFQPRO ?? '',
                $row->YFQSCR ?? '',
                trim($row->YFCRDT ?? ''),
                trim($row->YFCRTM ?? ''),
                trim($row->YFCRUS ?? '')
            );
        })->all();
    }
}
