<?php

namespace App\Http\Controllers;

use App\Exports\MdiReportExport;
use App\Exports\RejectedProductionRecordsExport;
use App\Models\PartNumber;
use App\Models\RejectedProductionRecord;
use App\Models\WorkCenter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    /**
     * Vista principal con los reportes generales disponibles (mosaicos).
     */
    public function index()
    {
        return view('reports.index', [
            'hasPressAccess' => $this->userHasPressAccess(),
        ]);
    }

    private const PRESS_AREA = 'Estampado';

    /**
     * Los reportes de prensas (MDI y Rechazos de IoT) solo son para usuarios con al
     * menos una línea o una estación del área de prensas asignada.
     */
    private function userHasPressAccess(): bool
    {
        $user = Auth::user();

        return $user->lines()
                ->whereHas('area', fn ($query) => $query->where('name', self::PRESS_AREA))
                ->exists()
            || $user->workCenters()
                ->whereHas('line.area', fn ($query) => $query->where('name', self::PRESS_AREA))
                ->exists();
    }

    /**
     * Estaciones que el usuario puede consultar: las de sus líneas más las que tiene
     * asignadas directamente.
     */
    private function userWorkCenterIds(): array
    {
        $user = Auth::user();

        return WorkCenter::whereIn('line_id', $user->lines()->pluck('lines.id'))
            ->pluck('id')
            ->merge($user->workCenters()->pluck('work_centers.id'))
            ->unique()
            ->values()
            ->all();
    }

    private function authorizePressReports(): void
    {
        abort_unless($this->userHasPressAccess(), 403, 'Este reporte es solo para usuarios con líneas o estaciones de prensas asignadas.');
    }

    /**
     * Descarga el Reporte de MDI en Excel.
     *
     */
    public function exportMdi()
    {
        $this->authorizePressReports();

        $workCenterIds = $this->userWorkCenterIds();

        $parts = PartNumber::query()
            ->select([
                'areas.name AS area_name',
                'lines.name AS line_name',
                'work_centers.number AS work_number',
                'work_centers.name AS work_name',
                'part_numbers.number AS part_number',
                'part_numbers.name AS part_name',
                'attributes.value AS mid',
            ])
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('lines', 'work_centers.line_id', '=', 'lines.id')
            ->join('areas', 'lines.area_id', '=', 'areas.id')
            ->leftJoin('attributes', function ($join) {
                $join->on('attributes.attributable_id', '=', 'part_numbers.id')
                    ->where('attributes.attributable_type', '=', PartNumber::class)
                    ->where('attributes.key', '=', 'mid');
            })
            ->where('areas.name', self::PRESS_AREA)
            ->whereIn('work_centers.id', $workCenterIds)
            ->orderBy('areas.name')
            ->orderBy('lines.name')
            ->orderBy('work_centers.number')
            ->orderBy('part_numbers.number')
            ->get();

        $filename = 'MDI_' . Carbon::now()->format('YmdHis') .  '.xlsx';

        return Excel::download(new MdiReportExport($parts), $filename);
    }

    /**
     * Descarga el reporte "Rechazos de IoT" en Excel: producción que el recolector
     * leyó del PLC y no se pudo registrar, dentro del rango de fechas indicado.
     *
     * El recolector guarda cada rechazo como un JSON en rejected_production_records;
     * aquí se separa: los campos del JSON son los encabezados y sus valores el contenido.
     *
     * Como el Reporte de MDI, solo incluye las estaciones (work_center_id) de las
     * líneas asociadas al usuario autenticado. Un rechazo sin estación asignada no
     * pertenece a ninguna línea, así que se incluye siempre.
     */
    public function exportIotRejections(Request $request)
    {
        $this->authorizePressReports();

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ], [
            'start_date.required' => 'Selecciona la fecha de inicio.',
            'start_date.date' => 'La fecha de inicio no es una fecha válida.',
            'end_date.required' => 'Selecciona la fecha de fin.',
            'end_date.date' => 'La fecha de fin no es una fecha válida.',
            'end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        ]);

        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->endOfDay();

        // Estaciones del usuario: las de sus líneas y las asignadas directamente
        $workCenterIds = $this->userWorkCenterIds();

        $rows = RejectedProductionRecord::query()
            // El rechazo se inserta cuando ocurre o después: nada anterior al rango puede caer en él.
            ->where('created_at', '>=', $start)
            ->where(function ($query) use ($workCenterIds) {
                $query->whereIn('work_center_id', $workCenterIds)
                    ->orWhereNull('work_center_id');
            })
            ->orderBy('id')
            ->get()
            ->map(fn ($record) => ['fields' => $record->fields(), 'date' => $record->occurredAt()])
            ->filter(fn ($row) => ! empty($row['fields']) && $row['date']->between($start, $end))
            ->sortByDesc(fn ($row) => $row['date']->getTimestamp())
            ->pluck('fields')
            ->values();

        $filename = 'Rechazos_IoT_' . $start->format('Ymd') . '_' . $end->format('Ymd') . '.xlsx';

        return Excel::download(new RejectedProductionRecordsExport($rows), $filename);
    }
}
