<?php

namespace App\Http\Controllers;

use App\Models\History;
use App\Models\ProductionRecord;
use App\Models\Shift;
use App\Models\YF013;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            ])
            ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
            ->join('statuses', 'production_records.status_id', '=', 'statuses.id')
            ->join('lines', 'work_centers.line_id', '=', 'lines.id')
            ->where('production_records.synced_to_infor', false)
            ->whereIn('work_centers.name', $workCentersArray)
            ->when($date, function ($query, $date) {
                // Filtrar por fecha específica si se proporciona
                return $query->whereDate('production_records.planned_date', $date);
            }, function ($query) {
                // Si no hay fecha, usar el filtro semanal por defecto
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
            ->orderBy('production_records.planned_date', 'asc')
            ->orderBy('shifts.start_time', 'asc')
            ->paginate(10)
            ->withQueryString();


        // Calcular las variables fuera de la consulta para mejor legibilidad
        // $currentShift = Shift::getShift(Carbon::now());
        // $currentShiftDate = Shift::calculateCurrentShiftDate($currentShift, Carbon::now());
        // $workCentersArray = Auth::user()->workCenters->pluck('name')->toArray();

        // $productionRecords = ProductionRecord::query()
        //     ->select([
        //         'production_records.id AS production_id',
        //         'lines.name AS line_name',
        //         'work_centers.number AS work_number',
        //         'work_centers.name AS work_name',
        //         'part_numbers.number AS part_number',
        //         'part_numbers.name AS part_name',
        //         'part_numbers.production_rate AS production_rate',
        //         'part_numbers.efficiency AS efficiency',
        //         'production_records.shop_order_number AS shop_order_number',
        //         'production_records.planned_date AS planned_date',
        //         'production_records.planned_quantity AS planned_quantity',
        //         'production_records.produced_quantity AS produced_quantity',
        //         'production_records.scrap_quantity AS scrap_quantity',
        //         'shifts.abbreviation AS shift_abbreviation',
        //         'shifts.name AS shift_name',
        //         'shifts.start_time AS shift_start_time',
        //         'shifts.end_time AS shift_end_time',
        //         'statuses.name AS status_name',
        //         'production_records.production_start AS production_start',
        //         'production_records.production_end AS production_end'
        //     ])
        //     ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
        //     ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
        //     ->join('lines', 'work_centers.line_id', '=', 'lines.id')
        //     ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
        //     ->join('statuses', 'production_records.status_id', '=', 'statuses.id')
        //     ->whereIn('work_centers.name', $workCentersArray)
        //     ->where('production_records.planned_date', $currentShiftDate->toDateString())
        //     ->where('shifts.abbreviation', $currentShift->abbreviation)
        //     ->orderBy('work_centers.number', 'asc')
        //     ->orderBy('part_numbers.production_order', 'asc')
        //     ->orderBy('production_records.planned_date', 'asc')
        //     ->orderBy('shifts.abbreviation', 'asc')
        //     ->get();

        // // Agrupar por número de estación
        // $groupedByWorkStation = $productionRecords->groupBy('work_number');

        // Para usar en tu vista/documento:
        // foreach ($groupedByWorkStation as $workNumber => $records) {
        //     echo "<br>";
        //     echo "Estación #$workNumber:\n";

        //     foreach ($records as $record) {
        //         echo "<br>";
        //         echo "  - Parte: {$record->part_number} | Cantidad: {$record->planned_quantity}\n";
        //         // Más detalles aquí...
        //     }
        //     echo "<br>";
        // }

        // dd("Fin");

        return view('production-records.index', compact('productionRecords'));
    }

    /**
     *
     */
    public function summary(Request $request)
    {
        try {
            $user = Auth::user();

            // Cargar work centers una sola vez
            $workCenters = $user->workCenters;
            $workCenterNames = $workCenters->pluck('name')->toArray();

            // Validar y procesar filtros
            $filterCenter = $request->input('work_center');
            $searchPart = $request->input('search');

            // Manejo de fechas mejorado
            $startDate = $request->input('startDate')
                ? Carbon::parse($request->input('startDate'))->startOfDay()
                : Carbon::now()->startOfDay();

            $endDate = $request->input('endDate')
                ? Carbon::parse($request->input('endDate'))->endOfDay()
                : Carbon::now()->endOfDay();

            // Validar rango de fechas
            if ($startDate->gt($endDate)) {
                $endDate = $startDate->copy()->endOfDay();
            }

            // Consulta optimizada
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
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductionRecord $productionRecord)
    {
        $validated = $request->validate([
            'scrap_quantity' => ['required', 'integer', 'min:0'],
        ], [
            'scrap_quantity.min' => 'La cantidad de scrap no puede ser negativa.',
        ]);

        $productionRecord->update([
            'scrap_quantity' => $validated['scrap_quantity'],
        ]);


        try {

            $now = Carbon::now();

            $productionStart = $productionRecord->production_start
                ? Carbon::parse($productionRecord->production_start)
                : null;

            $productionEnd = $productionRecord->production_end
                ? Carbon::parse($productionRecord->production_end)
                : null;

            $inserted = YF013::query()
                ->insert([
                    'YFWRKC' => $productionRecord->partNumber->workCenter->number ?? '',
                    'YFWRKN' => $productionRecord->partNumber->workCenter->name ?? '',
                    'YFRDTE' => $productionRecord->planned_date
                        ? Carbon::parse($productionRecord->planned_date)->format('Ymd')
                        : '',
                    'YFSHFT' => $productionRecord->shift->abbreviation ?? '',
                    'YFPPNO' => '',
                    'YFSORD' => $productionRecord->shop_order_number ?? '',
                    'YFPROD' => $productionRecord->partNumber->number ?? '',
                    'YFSTIM' => $productionStart ? $productionStart->format('Hi') : '',
                    'YFETIM' => $productionEnd ? $productionEnd->format('Hi') : '',
                    'YFSDT' => $productionStart ? $productionStart->format('YmdHi') : '',
                    'YFEDT' => $productionEnd ? $productionEnd->format('YmdHi') : '',
                    'YFQPLA' => $productionRecord->planned_quantity ?: $productionRecord->produced_quantity,
                    'YFQPRO' => $productionRecord->produced_quantity - $productionRecord->scrap_quantity,
                    'YFQSCR' => $productionRecord->scrap_quantity ?? 0,
                    'YFSCRE' => ($productionRecord->scrap_quantity ?? 0) == 0 ? '' : 'RJ',
                    'YFCRDT' => $now->format('Ymd'),
                    'YFCRTM' => $now->format('His'),
                    'YFCRUS' => '',
                ]);

            if ($inserted) {
                $productionRecord->update([
                    'synced_to_infor' => true,
                    'synced_at' => $now,
                ]);
            }

            try {
                $conn = odbc_connect("Driver={Client Access ODBC Driver (32-bit)};System=192.168.200.7;Uid=LXSECOFR;Pwd=LXSECOFR", "", "");

                if ($conn === false) {
                    throw new Exception("Error al conectar con la base de datos Infor.");
                } else {
                    Log::info("Conexión a Infor establecida correctamente en " . date('Y-m-d H:i:s'));
                }

                $query = "CALL LX834OU.YSF013C";
                $result = odbc_exec($conn, $query);

                if ($result) {
                    Log::info("LX834OU.YSF013C : La consulta se ejecutó con éxito en " . date('Y-m-d H:i:s'));
                } else {
                    throw new Exception("LX834OU.YSF013C : Error en la consulta: " . odbc_errormsg($conn));
                }
            } catch (Exception $e) {
                Log::alert($e->getMessage());
            } finally {
                if (isset($conn)) {
                    odbc_close($conn);
                }
            }

            return redirect()->back()->with('success', 'Cantidad de scrap actualizada.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al sincronizar con Infor: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     *
     */
    public function chart()
    {
        // $productionRecords = ProductionRecord::join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
        //     ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
        //     ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
        //     ->join('statuses', 'production_records.status_id', '=', 'statuses.id')
        //     ->orderBy('work_centers.name', 'asc')
        //     ->orderBy('part_numbers.number', 'asc')
        //     ->orderBy('planned_date', 'asc')
        //     ->orderBy('shifts.start', 'asc')
        //     ->get([
        //         'production_records.id AS id',
        //         'work_centers.number AS work_center_number',
        //         'work_centers.name AS work_center_name',
        //         'part_numbers.number AS part_number',
        //         'part_numbers.name AS part_name',
        //         'production_records.planned_quantity AS planned_quantity',
        //         'production_records.produced_quantity AS produced_quantity',
        //         'production_records.planned_date AS planned_date',
        //         'shifts.abbreviation AS shift_name',
        //         'statuses.name AS status_name'
        //     ]);

        // $groupedData = [];

        // foreach ($productionRecords as $record) {
        //     if (!isset($groupedData[$record->work_center_number])) {
        //         $groupedData[$record->work_center_number] = [
        //             'work_center_name' => $record->work_center_name,
        //             'dates' => []
        //         ];
        //     }

        //     if (!isset($groupedData[$record->work_center_number]['dates'][$record->planned_date])) {
        //         $groupedData[$record->work_center_number]['dates'][$record->planned_date] = [
        //             'shifts' => []
        //         ];
        //     }

        //     if (!isset($groupedData[$record->work_center_number]['dates'][$record->planned_date]['shifts'][$record->shift_name])) {
        //         $groupedData[$record->work_center_number]['dates'][$record->planned_date]['shifts'][$record->shift_name] = [
        //             'part_numbers' => []
        //         ];
        //     }

        //     $groupedData[$record->work_center_number]['dates'][$record->planned_date]['shifts'][$record->shift_name]['part_numbers'][$record->part_number] = [
        //         'part_name' => $record->part_name,
        //         'planned_quantity' => $record->planned_quantity,
        //         'produced_quantity' => $record->produced_quantity,
        //     ];
        // }

        // return view('chart',  ['groupedData', $groupedData]);

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

        // Inicializamos un array para almacenar los datos agrupados por fecha, estación, turno y número de parte
        $groupedData = [];

        // Agrupamos los datos
        foreach ($productionRecords as $record) {
            // Convertimos la fecha a un formato de día (por ejemplo, YYYY-MM-DD)
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

        // Ahora pasamos estos datos a la vista
        return view('chart', [
            'groupedData' => $groupedData,
        ]);
    }

    /**
     *
     */
    public function getProductionPlan() {}

    /**
     *
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
     *
     */
    public function getHourlyProductionGraph()
    {
        $now = Carbon::now();

        $shift = Shift::query()
            ->where(function ($query) use ($now) {
                // Turno diurno: 08:00 - 20:00
                $query->where('name', 'Diurno') // Asegúrate de que esto coincida con el nombre del turno en tu tabla
                    ->whereTime('start_time', '<=', $now)
                    ->whereTime('end_time', '>', $now);
            })
            ->orWhere(function ($query) use ($now) {
                // Turno nocturno: 20:00 - 08:00
                $query->where('name', 'Nocturno')
                    ->where(function ($nestedQuery) use ($now) {
                        $nestedQuery->whereTime('start_time', '<=', $now) // Hoy entre 20:00 y 23:59
                            ->orWhereTime('end_time', '>=', $now); // Mañana entre 00:00 y 08:00
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

        // Convertir $groupedData al formato esperado por Chart.js
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

        // Agrupar por número de estación
        $groupedByWorkStation = $productionRecords->groupBy('work_number');

        // Procesar cada grupo para agregar cálculos
        $groupedByWorkStation = $groupedByWorkStation->map(function ($records) {
            return $records->map(function ($record) {
                // Calcular tiempo ciclo: 60 / production_rate
                $cycletime = ($record->production_rate > 0)
                    ? round(60 / $record->production_rate, 2)
                    : 0;

                // Calcular tiempo planeado: (tiempo ciclo * cantidad planeada) / 60
                $plannedTime = ($cycletime > 0 && $record->planned_quantity > 0)
                    ? round(($cycletime * $record->planned_quantity) / 60, 2)
                    : 0;

                // Calcular tiempo total en minutos
                $startTime = $record->production_start ? \Carbon\Carbon::parse($record->production_start) : null;
                $endTime = $record->production_end ? \Carbon\Carbon::parse($record->production_end) : null;
                $totalMinutes = ($startTime && $endTime) ? $startTime->diffInMinutes($endTime) : 0;

                // Calcular eficiencia: (tiempo planeado / tiempo total) * efficiency
                $calculatedEfficiency = ($totalMinutes > 0 && $plannedTime > 0)
                    ? round(($plannedTime / $totalMinutes) * $record->efficiency, 2)
                    : 0;

                // Agregar los cálculos al registro
                $record->calculated_cycletime = $cycletime;
                $record->calculated_planned_time = $plannedTime;
                $record->calculated_total_minutes = $totalMinutes;
                $record->calculated_efficiency = $calculatedEfficiency;

                return $record;
            });
        });

        // Generar el PDF
        $pdf = Pdf::loadView('production.report-pdf', [
            'groupedByWorkStation' => $groupedByWorkStation,
            'currentShiftDate' => $currentShiftDate
        ]);

        // Configurar el PDF en orientación horizontal (landscape)
        $pdf->setPaper('letter', 'landscape');

        // Descargar el PDF
        return $pdf->download('reporte-produccion-' . $currentShiftDate->format('Y-m-d') . '.pdf');
    }
}
