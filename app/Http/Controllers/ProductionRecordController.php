<?php

namespace App\Http\Controllers;

use App\Exports\ProductionSummaryExport;
use App\Models\History;
use App\Models\LineStoppageRecord;
use App\Models\ProductionRecord;
use App\Models\ScrapRecord;
use App\Models\Shift;
use App\Models\WorkCenter;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductionRecordController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $date = $request->input('date');

        $workCentersArray = Auth::user()->workCenters->pluck('name')->toArray();

        $productionRecords = ProductionRecord::query()
            ->select([
                'production_records.id AS production_id',
                'lines.color AS line_color',
                'work_centers.number AS work_number',
                'work_centers.name AS work_name',
                'part_numbers.number AS part_number',
                'part_numbers.name AS part_name',
                'production_records.shop_order_number as shop_order_number',
                'production_records.planned_date AS planned_date',
                'production_records.planned_quantity AS planned_quantity',
                'production_records.produced_quantity AS produced_quantity',
                'production_records.scrap_quantity AS scrap_quantity',
                'shifts.abbreviation AS shift_name',
                'statuses.name AS status_name',
                'production_records.synced_to_infor',
                'production_records.synced_at',
            ])
            ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
            ->join('statuses', 'production_records.status_id', '=', 'statuses.id')
            ->join('lines', 'work_centers.line_id', '=', 'lines.id')
            ->whereIn('work_centers.name', $workCentersArray)
            ->when($date, function ($query, $date) {
                return $query->whereDate('production_records.planned_date', $date);
            })
            ->when(!$date && !$search, function ($query) {
                return $query->whereBetween('production_records.planned_date', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                ]);
            })
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('part_numbers.number', 'like', "%{$search}%")
                        ->orWhere('part_numbers.name', 'like', "%{$search}%")
                        ->orWhere('work_centers.name', 'like', "%{$search}%")
                        ->orWhere('shifts.abbreviation', 'like', "%{$search}%")
                        ->orWhere('statuses.name', 'like', "%{$search}%")
                        ->orWhere('production_records.shop_order_number', 'like', "%{$search}%");
                });
            })
            ->orderBy('production_records.planned_date', 'desc')
            ->orderBy('shifts.start_time', 'asc')
            ->paginate(10)
            ->withQueryString();

        return view('production-records.index', compact('productionRecords'));
    }

    public function summary(Request $request)
    {
        try {
            $user = Auth::user();

            $workCenters = $user->workCenters;
            $workCenterNames = $workCenters->pluck('name')->toArray();

            $selectedCenters = $request->input('work_centers', []);
            $searchPart      = $request->input('search');
            $selectedShift   = $request->input('shift');

            $startDateStr = $request->input('startDate');
            $endDateStr   = $request->input('endDate');

            $startDate = $startDateStr ? Carbon::parse($startDateStr)->startOfDay() : Carbon::now()->startOfDay();
            $endDate   = $endDateStr   ? Carbon::parse($endDateStr)->endOfDay()     : Carbon::now()->endOfDay();

            if ($startDate->gt($endDate)) {
                $endDate = $startDate->copy()->endOfDay();
            }

            $shifts = Shift::orderBy('start_time')->get();

            $query = ProductionRecord::with(['partNumber.workCenter', 'shift'])
                ->select([
                    'work_centers.number AS work_number',
                    'work_centers.name AS work_name',
                    'part_numbers.number AS part_number',
                    'part_numbers.name AS part_name',
                    'production_records.planned_date',
                    'shifts.abbreviation AS shift_name',
                    'production_records.planned_quantity',
                    'production_records.produced_quantity',
                    'production_records.scrap_quantity',
                    'production_records.production_start',
                    'production_records.production_end',
                ])
                ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
                ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
                ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
                ->whereIn('work_centers.name', $workCenterNames)
                ->whereBetween('production_records.planned_date', [
                    $startDate->toDateString(),
                    $endDate->toDateString(),
                ]);

            if (!empty($selectedCenters)) {
                $query->whereIn('work_centers.name', $selectedCenters);
            }

            if (!empty($searchPart)) {
                $query->where('part_numbers.number', 'like', "%{$searchPart}%");
            }

            if (!empty($selectedShift)) {
                $query->where('shifts.abbreviation', $selectedShift);
            }

            $productionRecords = $query->orderBy('production_records.planned_date', 'desc')
                ->orderBy('shifts.start_time', 'asc')
                ->get();

            $hasFilters = $request->filled('search')
                || $request->filled('work_centers')
                || $request->filled('startDate')
                || $request->filled('endDate')
                || $request->filled('shift');

            return view('production-records.summary', [
                'productionRecords' => $productionRecords,
                'workCenters'       => $workCenters,
                'selectedCenters'   => $selectedCenters,
                'search'            => $searchPart,
                'startDate'         => $startDate->toDateString(),
                'endDate'           => $endDate->toDateString(),
                'shifts'            => $shifts,
                'selectedShift'     => $selectedShift,
                'hasFilters'        => $hasFilters,
            ]);
        } catch (\Exception $e) {
            Log::error('Error en resumen de producción: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar el resumen.');
        }
    }

    public function exportSummary(Request $request)
    {
        $workCenterNames = Auth::user()->workCenters->pluck('name')->toArray();

        $selectedCenters = $request->input('work_centers', []);
        $searchPart      = $request->input('search');
        $selectedShift   = $request->input('shift');

        $startDateStr = $request->input('startDate');
        $endDateStr   = $request->input('endDate');

        $startDate = $startDateStr ? Carbon::parse($startDateStr)->startOfDay() : Carbon::now()->startOfDay();
        $endDate   = $endDateStr   ? Carbon::parse($endDateStr)->endOfDay()     : Carbon::now()->endOfDay();

        if ($startDate->gt($endDate)) {
            $endDate = $startDate->copy()->endOfDay();
        }

        $query = ProductionRecord::select([
                'work_centers.number AS work_number',
                'work_centers.name AS work_name',
                'part_numbers.number AS part_number',
                'part_numbers.name AS part_name',
                'production_records.planned_date',
                'shifts.abbreviation AS shift_name',
                'production_records.planned_quantity',
                'production_records.produced_quantity',
                'production_records.production_start',
                'production_records.production_end',
            ])
            ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
            ->whereIn('work_centers.name', $workCenterNames)
            ->whereBetween('production_records.planned_date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ]);

        if (!empty($selectedCenters)) {
            $query->whereIn('work_centers.name', $selectedCenters);
        }
        if (!empty($searchPart)) {
            $query->where('part_numbers.number', 'like', "%{$searchPart}%");
        }
        if (!empty($selectedShift)) {
            $query->where('shifts.abbreviation', $selectedShift);
        }

        $records = $query->orderBy('production_records.planned_date', 'desc')
            ->orderBy('shifts.start_time', 'asc')
            ->get();

        $filename = 'ProductionReport_' . Carbon::now()->format('YmdHis') . '.xlsx';

        return Excel::download(new ProductionSummaryExport($records), $filename);
    }

    public function chart()
    {
        $productionRecords = ProductionRecord::join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
            ->join('statuses', 'production_records.status_id', '=', 'statuses.id')
            ->get([
                'production_records.id AS id',
                'work_centers.number AS work_center_number',
                'work_centers.name AS work_center_name',
                'part_numbers.number AS part_number',
                'part_numbers.name AS part_name',
                'production_records.planned_quantity AS planned_quantity',
                'production_records.produced_quantity AS produced_quantity',
                'production_records.planned_date AS planned_date',
                'shifts.abbreviation AS shift_name',
                'statuses.name AS status_name'
            ]);

        $groupedData = [];

        foreach ($productionRecords as $record) {
            $date = \Carbon\Carbon::parse($record->planned_date)->format('Y-m-d');

            if (!isset($groupedData[$date])) {
                $groupedData[$date] = [];
            }

            if (!isset($groupedData[$date][$record->work_center_name])) {
                $groupedData[$date][$record->work_center_name] = [];
            }

            if (!isset($groupedData[$date][$record->work_center_name][$record->shift_name])) {
                $groupedData[$date][$record->work_center_name][$record->shift_name] = [];
            }

            $groupedData[$date][$record->work_center_name][$record->shift_name][] = [
                'part_number' => $record->part_number,
                'planned_quantity' => $record->planned_quantity,
                'produced_quantity' => $record->produced_quantity,
            ];
        }

        return view('chart', [
            'groupedData' => $groupedData,
        ]);
    }

    public function getProductionRecords()
    {
        $now = Carbon::now();

        $shift = Shift::query()
            ->where(function ($query) use ($now) {
                $query->whereTime('start_time', '<=', $now->format('H:i'))
                    ->whereTime('end_time', '>', $now->format('H:i'));
            })
            ->orWhere(function ($query) use ($now) {
                $query->whereTime('start_time', '<=', $now->format('H:i'))
                    ->whereTime('end_time', '>=', $now->format('H:i'));
            })
            ->first();

        $productionRecords = ProductionRecord::join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
            ->where('planned_date', $now->format('Y-m-d'))
            ->orderBy('production_records.planned_date', 'asc')
            ->orderBy('shifts.start_time', 'asc')
            ->select([
                'work_centers.name AS work_center_name',
                'part_numbers.number AS part_number',
                'production_records.planned_quantity AS planned_quantity',
                'production_records.produced_quantity AS produced_quantity',
                'production_records.planned_date AS planned_date',
                'shifts.abbreviation AS shift_name',
            ])->get();

        $groupedByWorkCenter = $productionRecords->groupBy('work_center_name')
            ->map(function ($workCenterGroup) {
                return $workCenterGroup->groupBy('planned_date')
                    ->map(function ($dateGroup) {
                        return $dateGroup->groupBy('shift_name')
                            ->map(function ($shiftGroup) {
                                return $shiftGroup->map(function ($record) {
                                    return [
                                        'part_number' => $record->part_number,
                                        'planned_quantity' => $record->planned_quantity,
                                        'produced_quantity' => $record->produced_quantity,
                                        'difference' => $record->planned_quantity - $record->produced_quantity
                                    ];
                                });
                            });
                    });
            });

        return view('production-records.get-production-records', compact('groupedByWorkCenter'));
    }

    public function getHourlyProductionGraph()
    {
        $now = Carbon::now();

        $shift = Shift::query()
            ->where(function ($query) use ($now) {
                $query->where('name', 'Diurno')
                    ->whereTime('start_time', '<=', $now)
                    ->whereTime('end_time', '>', $now);
            })
            ->orWhere(function ($query) use ($now) {
                $query->where('name', 'Nocturno')
                    ->where(function ($nestedQuery) use ($now) {
                        $nestedQuery->whereTime('start_time', '<=', $now)
                            ->orWhereTime('end_time', '>=', $now);
                    });
            })
            ->first();

        $startDateTime = $now->copy()->setTimeFromTimeString($shift->start_time);

        if ($shift->abbreviation === 'N') {
            $endDateTime = $now->copy()->addDay()->setTimeFromTimeString($shift->end_time);
        } else {
            $endDateTime = $now->copy()->setTimeFromTimeString($shift->end_time);
        }

        $historyRecords = History::join('part_numbers', 'part_numbers.id', '=', 'histories.part_number_id')
            ->join('work_centers', 'work_centers.id', '=', 'part_numbers.work_center_id')
            ->join('production_records', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('shifts', 'shifts.id', '=', 'production_records.shift_id')
            ->where('shifts.id', '=', $shift->id)
            ->whereBetween('histories.created_at', [$startDateTime, $endDateTime])
            ->orderBy('work_centers.number', 'asc')
            ->orderBy('histories.created_at', 'asc')
            ->select(
                'work_centers.number AS work_number',
                'work_centers.name AS work_name',
                'part_numbers.id AS part_id',
                'part_numbers.number AS part_number',
                'part_numbers.name AS part_name',
                'production_records.planned_quantity AS planned_quantity',
                'shifts.abbreviation',
                'histories.created_at',
                'histories.quantity'
            )
            ->get();

        $groupedData = [];

        foreach ($historyRecords as $record) {
            $dateTime = Carbon::parse($record->created_at)->format('Y-m-d H:00');

            if (!isset($groupedData[$record->work_name])) {
                $groupedData[$record->work_name] = [];
            }

            if (!isset($groupedData[$record->work_name][$record->part_number])) {
                $groupedData[$record->work_name][$record->part_number] = [
                    'planned_quantity' => $record->planned_quantity,
                    'production_per_hour' => []
                ];
            }

            if (!isset($groupedData[$record->work_name][$record->part_number]['production_per_hour'][$dateTime])) {
                $groupedData[$record->work_name][$record->part_number]['production_per_hour'][$dateTime] = [];
            }

            if (!isset($groupedData[$record->work_name][$record->part_number]['production_per_hour'][$dateTime][$record->part_number])) {
                $groupedData[$record->work_name][$record->part_number]['production_per_hour'][$dateTime][$record->part_number] = $record->quantity;
            } else {
                $groupedData[$record->work_name][$record->part_number]['production_per_hour'][$dateTime][$record->part_number] = max(
                    $groupedData[$record->work_name][$record->part_number]['production_per_hour'][$dateTime][$record->part_number],
                    $record->quantity
                );
            }
        }

        $hours = $startDateTime->diffInHours($endDateTime);

        $chartData = [];

        foreach ($groupedData as $workName => $parts) {
            foreach ($parts as $partNumber => $data) {
                $labels = array_keys($data['production_per_hour']);

                $plannedPerHour = round($data['planned_quantity'] / $hours, 3);
                $plannedData = [];
                $accumulatedPlanned = 0;

                foreach ($labels as $label) {
                    $accumulatedPlanned += $plannedPerHour;
                    $plannedData[] = $accumulatedPlanned;
                }

                $productionData = [];
                foreach ($data['production_per_hour'] as $hourData) {
                    $productionData[] = array_sum($hourData);
                }

                $chartData[] = [
                    'work_name' => $workName,
                    'part_number' => $partNumber,
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'label' => 'Cantidad Planeada Por Hora',
                            'data' => $plannedData,
                            'backgroundColor' => 'rgba(255, 159, 64, 0.2)',
                            'borderColor' => 'rgb(255, 159, 64)',
                            'borderWidth' => 2,
                            'stack' => 'combined'
                        ],
                        [
                            'label' => 'Cantidad Producida por Hora',
                            'data' => $productionData,
                            'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                            'borderColor' => 'rgb(75, 192, 192)',
                            'borderWidth' => 2,
                            'stack' => 'combined'
                        ]
                    ],
                    'chart_id' => Str::ulid()
                ];
            }
        }

        return view('chart_test', [
            'chartData' => $chartData
        ]);
    }

    public function exportProductionReport()
    {
        $currentShift = Shift::getShift(Carbon::now());
        $currentShiftDate = Shift::calculateCurrentShiftDate($currentShift, Carbon::now());
        $workCentersArray = Auth::user()->workCenters->pluck('name')->toArray();

        $productionRecords = ProductionRecord::query()
            ->select([
                'production_records.id AS production_id',
                'lines.name AS line_name',
                'work_centers.number AS work_number',
                'work_centers.name AS work_name',
                'part_numbers.number AS part_number',
                'part_numbers.name AS part_name',
                'part_numbers.production_rate AS production_rate',
                'part_numbers.efficiency AS efficiency',
                'production_records.shop_order_number AS shop_order_number',
                'production_records.planned_date AS planned_date',
                'production_records.planned_quantity AS planned_quantity',
                'production_records.produced_quantity AS produced_quantity',
                'production_records.scrap_quantity AS scrap_quantity',
                'shifts.abbreviation AS shift_abbreviation',
                'shifts.name AS shift_name',
                'shifts.start_time AS shift_start_time',
                'shifts.end_time AS shift_end_time',
                'statuses.name AS status_name',
                'production_records.production_start AS production_start',
                'production_records.production_end AS production_end'
            ])
            ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('lines', 'work_centers.line_id', '=', 'lines.id')
            ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
            ->join('statuses', 'production_records.status_id', '=', 'statuses.id')
            ->whereIn('work_centers.name', $workCentersArray)
            ->where('production_records.planned_date', $currentShiftDate->toDateString())
            ->where('shifts.abbreviation', $currentShift->abbreviation)
            ->orderBy('work_centers.number', 'asc')
            ->orderBy('part_numbers.production_order', 'asc')
            ->orderBy('production_records.planned_date', 'asc')
            ->orderBy('shifts.abbreviation', 'asc')
            ->get();

        $groupedByWorkStation = $productionRecords->groupBy('work_number');

        $groupedByWorkStation = $groupedByWorkStation->map(function ($records) {
            return $records->map(function ($record) {
                $cycletime = ($record->production_rate > 0)
                    ? round(60 / $record->production_rate, 2)
                    : 0;

                $plannedTime = ($cycletime > 0 && $record->planned_quantity > 0)
                    ? round(($cycletime * $record->planned_quantity) / 60, 2)
                    : 0;

                $startTime = $record->production_start ? \Carbon\Carbon::parse($record->production_start) : null;
                $endTime = $record->production_end ? \Carbon\Carbon::parse($record->production_end) : null;
                $totalMinutes = ($startTime && $endTime) ? $startTime->diffInMinutes($endTime) : 0;

                $calculatedEfficiency = ($totalMinutes > 0 && $plannedTime > 0)
                    ? round(($plannedTime / $totalMinutes) * $record->efficiency, 2)
                    : 0;

                $record->calculated_cycletime = $cycletime;
                $record->calculated_planned_time = $plannedTime;
                $record->calculated_total_minutes = $totalMinutes;
                $record->calculated_efficiency = $calculatedEfficiency;

                return $record;
            });
        });

        $pdf = Pdf::loadView('production.report-pdf', [
            'groupedByWorkStation' => $groupedByWorkStation,
            'currentShiftDate' => $currentShiftDate
        ]);

        $pdf->setPaper('letter', 'landscape');

        return $pdf->download('reporte-produccion-' . $currentShiftDate->format('Y-m-d') . '.pdf');
    }

    public function showExportForm()
    {
        $user = Auth::user();

        // Obtener las líneas a las que tiene acceso el usuario
        $lines = $user->lines()->orderBy('name')->get();

        // Obtener los centros de trabajo a los que tiene acceso el usuario
        $workCenters = $user->workCenters()->orderBy('name')->get();

        return view('production-records.export-form', compact('lines', 'workCenters'));
    }

    public function exportProductionReportFiltered(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'lines' => 'nullable|array',
            'lines.*' => 'exists:lines,id',
            'work_centers' => 'nullable|array',
            'work_centers.*' => 'exists:work_centers,id',
        ]);

        // Validar que al menos se seleccione una línea o estación
        if (empty($request->lines) && empty($request->work_centers)) {
            return redirect()->back()->withErrors('Debe seleccionar al menos una línea o una estación.');
        }

        $user = Auth::user();
        $workCentersArray = $user->workCenters->pluck('name')->toArray();

        // Obtener todos los turnos
        $shifts = Shift::getAllShifts();

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();

        // Obtener todas las estaciones posibles basadas en filtros
        $workCentersQuery = WorkCenter::query()
            ->select(['work_centers.id', 'work_centers.number', 'work_centers.name', 'lines.name as line_name', 'areas.name as area_name'])
            ->join('lines', 'work_centers.line_id', '=', 'lines.id')
            ->join('areas', 'lines.area_id', '=', 'areas.id')
            ->whereIn('work_centers.name', $workCentersArray);

        if (!empty($request->lines)) {
            $workCentersQuery->whereIn('lines.id', $request->lines);
        }

        if (!empty($request->work_centers)) {
            $workCentersQuery->whereIn('work_centers.id', $request->work_centers);
        }

        $workCenters = $workCentersQuery->get();

        // Crear grupos basados en combinaciones de estación, fecha y turno
        $groups = [];

        // Generar todas las combinaciones posibles de estación, fecha y turno
        foreach ($workCenters as $workCenter) {
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                foreach ($shifts as $shift) {
                    // Determinar el rango de tiempo del turno para esta fecha
                    $shiftDateTimeRange = Shift::getShiftDateTimeRange($shift, $date);

                    if (!$shiftDateTimeRange) {
                        continue;
                    }

                    $shiftStart = $shiftDateTimeRange->startDateTime;
                    $shiftEnd = $shiftDateTimeRange->endDateTime;

                    // Verificar si el rango del turno intersecta con el rango de fechas filtrado
                    if ($shiftStart->greaterThan($endDate) || $shiftEnd->lessThan($startDate)) {
                        continue;
                    }

                    $key = $workCenter->number . '|' . $date->format('Y-m-d') . '|' . $shift->abbreviation;

                    // Obtener registros de producción para esta combinación
                    $productionRecords = ProductionRecord::query()
                        ->select([
                            'production_records.id AS production_id',
                            'lines.name AS line_name',
                            'areas.name AS area_name',
                            'lines.color AS line_color',
                            'work_centers.number AS work_number',
                            'work_centers.name AS work_name',
                            'part_numbers.number AS part_number',
                            'part_numbers.name AS part_name',
                            'part_numbers.production_rate AS production_rate',
                            'part_numbers.efficiency AS efficiency',
                            'production_records.shop_order_number AS shop_order_number',
                            'production_records.planned_date AS planned_date',
                            'production_records.planned_quantity AS planned_quantity',
                            'production_records.produced_quantity AS produced_quantity',
                            'production_records.scrap_quantity AS scrap_quantity',
                            'shifts.abbreviation AS shift_abbreviation',
                            'shifts.name AS shift_name',
                            'shifts.start_time AS shift_start_time',
                            'shifts.end_time AS shift_end_time',
                            'statuses.name AS status_name',
                            'production_records.production_start AS production_start',
                            'production_records.production_end AS production_end'
                        ])
                        ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
                        ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
                        ->join('lines', 'work_centers.line_id', '=', 'lines.id')
                        ->join('areas', 'lines.area_id', '=', 'areas.id')
                        ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
                        ->join('statuses', 'production_records.status_id', '=', 'statuses.id')
                        ->where('work_centers.id', $workCenter->id)
                        ->where('production_records.planned_date', $date->format('Y-m-d'))
                        ->where('shifts.id', $shift->id)
                        ->orderBy('part_numbers.production_order', 'asc')
                        ->get();

                    // Calcular campos adicionales para producción
                    $productionRecords = $productionRecords->map(function ($record) {
                        $cycletime = ($record->production_rate > 0)
                            ? round(60 / $record->production_rate, 2)
                            : 0;

                        $plannedTime = ($cycletime > 0 && $record->planned_quantity > 0)
                            ? round(($cycletime * $record->planned_quantity) / 60, 2)
                            : 0;

                        $startTime = $record->production_start ? Carbon::parse($record->production_start) : null;
                        $endTime = $record->production_end ? Carbon::parse($record->production_end) : null;
                        $totalMinutes = ($startTime && $endTime) ? $startTime->diffInMinutes($endTime) : 0;

                        $calculatedEfficiency = ($totalMinutes > 0 && $plannedTime > 0)
                            ? round(($plannedTime / $totalMinutes) * $record->efficiency, 2)
                            : 0;

                        $record->calculated_cycletime = $cycletime;
                        $record->calculated_planned_time = $plannedTime;
                        $record->calculated_total_minutes = $totalMinutes;
                        $record->calculated_efficiency = $calculatedEfficiency;

                        return $record;
                    });

                    // Obtener scrap para esta estación, fecha y turno
                    $scrapRecords = ScrapRecord::query()
                        ->select([
                            'scrap_records.id AS scrap_record_id',
                            'work_centers.name AS work_name',
                            'work_centers.number AS work_number',
                            'part_numbers.name AS part_name',
                            'part_numbers.number AS part_number',
                            'type_scraps.name AS type_scrap_name',
                            'scraps.code AS scrap_code',
                            'scraps.name AS scrap_name',
                            'scrap_records.quantity AS quantity',
                            'scrap_records.created_at',
                            'scrap_records.updated_at'
                        ])
                        ->join('scraps', 'scrap_records.scrap_id', '=', 'scraps.id')
                        ->join('type_scraps', 'scraps.type_scrap_id', '=', 'type_scraps.id')
                        ->join('part_numbers', 'scrap_records.part_number_id', '=', 'part_numbers.id')
                        ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
                        ->join('lines', 'work_centers.line_id', '=', 'lines.id')
                        ->where('work_centers.id', $workCenter->id)
                        ->whereBetween('scrap_records.created_at', [$shiftStart, $shiftEnd])
                        ->get();

                    // Obtener paros de línea para esta estación, fecha y turno
                    $lineStoppageRecords = LineStoppageRecord::query()
                        ->select([
                            'line_stoppage_records.id AS line_stoppage_record_id',
                            'line_stoppages.name AS line_stoppage_name',
                            'work_centers.number AS work_number',
                            'work_centers.name AS work_name',
                            'line_stoppage_records.start_time AS start_time',
                            'line_stoppage_records.end_time AS end_time',
                            'line_stoppage_records.minutes_stoppage AS minutes_stoppage',
                            'type_line_stoppages.name AS type_line_stoppage_name'
                        ])
                        ->join('line_stoppages', 'line_stoppage_records.line_stoppage_id', 'line_stoppages.id')
                        ->join('type_line_stoppages', 'line_stoppages.type_line_stoppage_id', '=', 'type_line_stoppages.id')
                        ->join('work_centers', 'line_stoppage_records.work_center_id', 'work_centers.id')
                        ->where('work_centers.id', $workCenter->id)
                        ->whereBetween('line_stoppage_records.start_time', [$shiftStart, $shiftEnd])
                        ->get();

                    // Solo agregar grupo si hay al menos un registro (producción, scrap o paros)
                    if ($productionRecords->isNotEmpty() || $scrapRecords->isNotEmpty() || $lineStoppageRecords->isNotEmpty()) {
                        $groups[$key] = [
                            'work_number' => $workCenter->number,
                            'work_name' => $workCenter->name,
                            'line_name' => $workCenter->line_name,
                            'area_name' => $workCenter->area_name,
                            'shift_name' => $shift->name,
                            'shift_abbreviation' => $shift->abbreviation,
                            'shift_start_time' => $shift->start_time,
                            'shift_end_time' => $shift->end_time,
                            'planned_date' => $date->format('Y-m-d'),
                            'records' => $productionRecords,
                            'scrap_records' => $scrapRecords,
                            'line_stoppage_records' => $lineStoppageRecords,
                        ];
                    }
                }
            }
        }

        // Ordenar los grupos
        uasort($groups, function ($a, $b) {
            if ($a['work_number'] == $b['work_number']) {
                if ($a['planned_date'] == $b['planned_date']) {
                    return strcmp($a['shift_abbreviation'], $b['shift_abbreviation']);
                }
                return strcmp($a['planned_date'], $b['planned_date']);
            }
            return $a['work_number'] <=> $b['work_number'];
        });

        $pdf = Pdf::loadView('production.report-pdf-filtered', [
            'groups' => array_values($groups), // Convertir a array indexado
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);

        $pdf->setPaper('letter', 'landscape');

        return $pdf->download('FOR-IOT-01' . Carbon::now()->format('YmdHis') . '.pdf');
    }
}
