<?php

namespace App\Http\Controllers;

use App\Exports\MdiReportExport;
use App\Exports\OverproductionExport;
use App\Exports\RejectedProductionRecordsExport;
use App\Models\PartNumber;
use App\Models\RejectedProductionRecord;
use App\Models\WorkCenter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            'hasWorkCenters' => $this->userWorkCenterIds() !== [],
            'overproductionFloor' => self::OVERPRODUCTION_FLOOR_DATE,
        ]);
    }

    private const PRESS_AREA = 'Estampado';

    /** Sobreproducción: no se consulta antes de esta fecha. */
    private const OVERPRODUCTION_FLOOR_DATE = '2026-11-01';

    /** Estatus de production_records que cuentan como sobreproducción: No planeado, Expirado, No verificado y Cancelado. */
    private const OVERPRODUCTION_STATUS_IDS = [24, 19, 14, 12];

    private const OVERPRODUCTION_ITEM_CLASSES = ['F1', 'M3', 'M2', 'M1'];

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

    /**
     * Descarga el reporte "Sobreproducción" en Excel: números de parte que produjeron
     * sin tener plan ni orden de Infor (sin shop_order_number), dentro del rango indicado.
     *
     * Solo incluye las estaciones del usuario (sus líneas y las asignadas directamente).
     * El administrador recibe además las columnas técnicas (IDs, fechas de registro, sync).
     */
    public function exportOverproduction(Request $request)
    {
        // Bolsa de errores propia: las fechas comparten nombre con el modal de Rechazos de IoT
        $validated = $request->validateWithBag('overproduction', [
            'start_date' => 'required|date|after_or_equal:' . self::OVERPRODUCTION_FLOOR_DATE,
            'end_date' => 'required|date|after_or_equal:start_date',
        ], [
            'start_date.required' => 'Selecciona la fecha de inicio.',
            'start_date.date' => 'La fecha de inicio no es una fecha válida.',
            'start_date.after_or_equal' => 'La fecha de inicio no puede ser anterior a noviembre de 2026.',
            'end_date.required' => 'Selecciona la fecha de fin.',
            'end_date.date' => 'La fecha de fin no es una fecha válida.',
            'end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        ]);

        $isAdmin = Auth::user()->hasRole('Administrador');

        // [encabezado => columna]; las marcadas con $adminOnly solo las ve el administrador
        $adminOnly = ['ID', 'PART ID', 'IS OBSOLETE', 'PRODUCTION START', 'PRODUCTION END', 'CREATED AT', 'UPDATED AT', 'SYNCED TO INFOR', 'SYNCED AT'];
        $columns = [
            'ID' => 'pr.id',
            'LINE' => 'ln.name',
            'WORK NAME' => 'wc.number',
            'WORK CENTER' => 'wc.name',
            'PART ID' => 'pn.id',
            'PART NUMBER' => 'pn.number',
            'PART NAME' => 'pn.name',
            'ITEM CLASS' => 'ic.abbreviation',
            'ORDER NUMBER' => 'pr.shop_order_number',
            'PLANNED DATE' => 'pr.planned_date',
            'SHIFT' => 's.abbreviation',
            'PLANNED QUANTITY' => 'pr.planned_quantity',
            'PRODUCED QUANTITY' => 'pr.produced_quantity',
            'IS OBSOLETE' => "CASE WHEN pn.is_obsolete = 1 THEN 'TRUE' ELSE 'FALSE' END",
            'STATUS' => 't.name',
            'PRODUCTION START' => 'pr.production_start',
            'PRODUCTION END' => 'pr.production_end',
            'CREATED AT' => 'pr.created_at',
            'UPDATED AT' => 'pr.updated_at',
            'INFOR SYNC' => "CASE WHEN swc.sync_from IS NOT NULL THEN 'Sí' ELSE 'No' END",
            'INFOR SYNC DATE' => 'swc.sync_from',
            'SYNCED TO INFOR' => "CASE WHEN pr.synced_to_infor = 1 THEN 'TRUE' ELSE 'FALSE' END",
            'SYNCED AT' => 'pr.synced_at',
        ];
        if (! $isAdmin) {
            $columns = array_diff_key($columns, array_flip($adminOnly));
        }

        $rows = DB::table('production_records as pr')
            ->selectRaw(collect($columns)->map(fn ($col, $alias) => "$col AS \"$alias\"")->implode(', '))
            ->join('part_numbers as pn', 'pr.part_number_id', '=', 'pn.id')
            ->join('shifts as s', 'pr.shift_id', '=', 's.id')
            ->join('statuses as t', 'pr.status_id', '=', 't.id')
            ->join('work_centers as wc', 'pn.work_center_id', '=', 'wc.id')
            ->join('item_classes as ic', 'pn.item_class_id', '=', 'ic.id')
            ->leftJoin('lines as ln', 'wc.line_id', '=', 'ln.id')
            ->leftJoin('infor_sync_work_centers as swc', 'swc.work_center_id', '=', 'wc.id')
            ->whereBetween('pr.planned_date', [$validated['start_date'], $validated['end_date']])
            ->whereIn('t.id', self::OVERPRODUCTION_STATUS_IDS)
            ->whereNull('pr.shop_order_number')
            ->whereIn('ic.abbreviation', self::OVERPRODUCTION_ITEM_CLASSES)
            ->whereIn('wc.id', $this->userWorkCenterIds())
            ->orderBy('pr.planned_date')->orderBy('s.abbreviation')->orderBy('wc.number')->orderBy('pn.number')
            ->get()
            ->map(fn ($row) => array_values((array) $row));

        $filename = 'OVERPRODUCTION_' . Carbon::now()->format('YmdHis') . '.xlsx';

        return Excel::download(new OverproductionExport($rows, array_keys($columns)), $filename);
    }
}
