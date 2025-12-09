<?php

namespace App\Http\Controllers;

use App\Models\History;
use App\Models\ProductionRecord;
use App\Models\Shift;
use App\Models\WorkCenter;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductionRecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
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
            }, function ($query) {
                return $query->whereBetween('production_records.planned_date', [
                    Carbon::now()->startOfWeek()->subWeek(),
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
            // ->orderBy('production_records.synced_to_infor', 'asc')
            ->orderBy('production_records.planned_date', 'desc')
            ->orderBy('shifts.start_time', 'asc')
            ->paginate(10)
            ->withQueryString();

        return view('production-records.index', compact('productionRecords'));
    }

    /**
     * Summary view
     */
    public function summary(Request $request)
    {
        try {
            $user = Auth::user();

            $workCenters = $user->workCenters;
            $workCenterNames = $workCenters->pluck('name')->toArray();

            $filterCenter = $request->input('work_center');
            $searchPart = $request->input('search');

            $startDate = $request->input('startDate')
                ? Carbon::parse($request->input('startDate'))->startOfDay()
                : Carbon::now()->startOfDay();

            $endDate = $request->input('endDate')
                ? Carbon::parse($request->input('endDate'))->endOfDay()
                : Carbon::now()->endOfDay();

            if ($startDate->gt($endDate)) {
                $endDate = $startDate->copy()->endOfDay();
            }

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
                    $endDate->toDateString()
                ])
                ->when($filterCenter, function ($q, $filterCenter) {
                    return $q->where('work_centers.name', $filterCenter);
                })
                ->when($searchPart, function ($q, $searchPart) {
                    return $q->where('part_numbers.number', 'like', "%{$searchPart}%");
                })
                ->orderBy('production_records.planned_date', 'asc')
                ->orderBy('shifts.start_time', 'asc')
                ->orderBy('part_numbers.number', 'asc');

            $productionRecords = $query->get();

            return view('production-records.summary', [
                'productionRecords' => $productionRecords,
                'workCenters' => $workCenters,
                'selectedCenter' => $filterCenter,
                'search' => $searchPart,
                'startDate' => $startDate->toDateString(),
                'endDate' => $endDate->toDateString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error en resumen de producción: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Ocurrió un error al cargar el resumen. Por favor, intenta nuevamente.');
        }
    }

    /**
     * Chart view
     */
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

    /**
     * Get production records
     */
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

    /**
     * Get hourly production graph
     */
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

    /**
     * Export production report
     */
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

    /**
     * Mostrar formulario para filtrar reporte PDF
     */
    public function showExportForm()
    {
        $user = Auth::user();

        // Obtener las líneas a las que tiene acceso el usuario
        $lines = $user->lines()->orderBy('name')->get();

        // Obtener los centros de trabajo a los que tiene acceso el usuario
        $workCenters = $user->workCenters()->orderBy('name')->get();

        // Obtener todos los turnos
        $shifts = Shift::orderBy('start_time')->get();

        return view('production-records.export-form', compact('lines', 'workCenters', 'shifts'));
    }

    /**
     * Exportar reporte de producción filtrado
     */
    /**
     * Exportar reporte de producción filtrado
     */
    public function exportProductionReportFiltered(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'lines' => 'nullable|array',
            'lines.*' => 'exists:lines,id',
            'work_centers' => 'nullable|array',
            'work_centers.*' => 'exists:work_centers,id',
            'shifts' => 'nullable|array',
            'shifts.*' => 'exists:shifts,id',
        ]);

        // Validar que al menos se seleccione una línea, estación o turno
        if (empty($request->lines) && empty($request->work_centers) && empty($request->shifts)) {
            return redirect()->back()->withErrors('Debe seleccionar al menos una línea, una estación o un turno.');
        }

        $user = Auth::user();
        $workCentersArray = $user->workCenters->pluck('name')->toArray();

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();

        // Construir la consulta base con área
        $query = ProductionRecord::query()
            ->select([
                'production_records.id AS production_id',
                'lines.name AS line_name',
                'areas.name AS area_name', // Añadir área
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
            ->join('areas', 'lines.area_id', '=', 'areas.id') // Unir con áreas
            ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
            ->join('statuses', 'production_records.status_id', '=', 'statuses.id')
            ->whereIn('work_centers.name', $workCentersArray)
            ->whereBetween('production_records.planned_date', [
                $startDate->toDateString(),
                $endDate->toDateString()
            ]);

        // Filtrar por líneas si se proporcionan
        if (!empty($request->lines)) {
            $query->whereIn('lines.id', $request->lines);
        }

        // Filtrar por centros de trabajo si se proporcionan
        if (!empty($request->work_centers)) {
            $query->whereIn('work_centers.id', $request->work_centers);
        }

        // Filtrar por turnos si se proporcionan
        if (!empty($request->shifts)) {
            $query->whereIn('shifts.id', $request->shifts);
        }

        $productionRecords = $query->orderBy('work_centers.number', 'asc')
            ->orderBy('part_numbers.production_order', 'asc')
            ->orderBy('production_records.planned_date', 'asc')
            ->orderBy('shifts.abbreviation', 'asc')
            ->get();

        // Agrupar por work_center, planned_date y shift_id
        $grouped = $productionRecords->groupBy(function ($record) {
            return $record->work_number . '|' . $record->planned_date . '|' . $record->shift_abbreviation;
        });

        $groups = [];
        foreach ($grouped as $key => $records) {
            $firstRecord = $records->first();

            // Calcular los campos adicionales para cada registro
            $records = $records->map(function ($record) {
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

            $groups[] = [
                'work_number' => $firstRecord->work_number,
                'work_name' => $firstRecord->work_name,
                'line_name' => $firstRecord->line_name,
                'area_name' => $firstRecord->area_name,
                'shift_name' => $firstRecord->shift_name,
                'shift_abbreviation' => $firstRecord->shift_abbreviation,
                'shift_start_time' => $firstRecord->shift_start_time,
                'shift_end_time' => $firstRecord->shift_end_time,
                'planned_date' => $firstRecord->planned_date,
                'records' => $records,
            ];
        }

        // Ordenar los grupos
        usort($groups, function ($a, $b) {
            if ($a['work_number'] == $b['work_number']) {
                if ($a['planned_date'] == $b['planned_date']) {
                    return $a['shift_abbreviation'] <=> $b['shift_abbreviation'];
                }
                return $a['planned_date'] <=> $b['planned_date'];
            }
            return $a['work_number'] <=> $b['work_number'];
        });

        $pdf = Pdf::loadView('production.report-pdf-filtered', [
            'groups' => $groups,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);

        $pdf->setPaper('letter', 'landscape');

        return $pdf->download('FOR-IOT-01' . Carbon::now()->format('YmdHis') . '.pdf');
    }
}
