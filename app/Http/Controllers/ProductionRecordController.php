<?php

namespace App\Http\Controllers;

use App\Models\History;
use App\Models\PartNumber;
use App\Models\ProductionRecord;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use JeroenNoten\LaravelAdminLte\View\Components\Widget\Card;

use function Laravel\Prompts\select;

class ProductionRecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function update(Request $request, string $id)
    {
        //
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
    public function showPlanAndProduction()
    {
        $productionRecords = ProductionRecord::join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
            // ->join('statuses', 'production_records.status_id', '=', 'statuses.id')
            ->orderBy('production_records.planned_date', 'asc')
            ->orderBy('shifts.start', 'asc')
            ->get([
                // 'production_records.id AS id',
                // 'work_centers.number AS work_center_number',
                'work_centers.name AS work_center_name',
                'part_numbers.number AS part_number',
                // 'part_numbers.name AS part_name',
                'production_records.planned_quantity AS planned_quantity',
                'production_records.produced_quantity AS produced_quantity',
                'production_records.planned_date AS planned_date',
                'shifts.abbreviation AS shift_name',
                // 'statuses.name AS status_name'
            ]);

        $groupedByWorkCenter = $productionRecords->groupBy('work_center_name')
            ->map(function ($workCenterGroup) {
                return $workCenterGroup->groupBy('planned_date')
                    ->map(function ($dateGroup) {
                        return $dateGroup->groupBy('shift_name')
                            ->map(function ($shiftGroup) {
                                return $shiftGroup->map(function ($record) {
                                    // Guardamos la información que quieres en un formato adecuado
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

        return view('production-records.show-plan-production', compact('groupedByWorkCenter'));
    }

    /**
     *
     */
    public function getHourlyProductionRecord()
    {

        $now = Carbon::now();

        $startDate = $now->copy()->subHours(8)->startOfHour()->format('Y-m-d H:i:s');
        $endDate = $now->copy()->addHours(8)->startOfHour()->format('Y-m-d H:i:s');

        // $historyRecords = History::join('part_numbers', 'part_numbers.id', '=', 'histories.part_number_id')
        //     ->join('work_centers', 'work_centers.id', '=', 'part_numbers.work_center_id')
        //     ->whereBetween('histories.created_at', [$startDate, $endDate])
        //     ->orderBy('histories.created_at', 'desc')
        //     ->select('part_numbers.number AS part_number', 'part_numbers.name AS part_name', 'work_centers.number AS work_number', 'work_centers.name AS work_name', 'histories.quantity')
        //     ->get();

        $historyRecords = History::join('part_numbers', 'part_numbers.id', '=', 'histories.part_number_id')
            ->join('work_centers', 'work_centers.id', '=', 'part_numbers.work_center_id')
            ->whereBetween('histories.created_at', [$startDate, $endDate])
            ->select(
                'work_centers.name AS work_name',
                'part_numbers.number AS part_number',
                'histories.created_at', // Usamos FORMAT() para SQL Server
                DB::raw('MAX(histories.quantity) AS max_quantity') // Obtenemos la cantidad más alta
            )
            ->groupBy('work_centers.name', 'part_numbers.number', 'hour') // Agrupamos por work_name, part_number y hora
            ->orderBy('work_centers.name', 'asc')
            ->orderBy('part_numbers.number', 'asc')
            ->orderBy('hour', 'asc')
            ->get();

        $groupedRecords = [];

        foreach ($historyRecords as $record) {
            // Asegúrate de que el array para cada work_name y part_number esté inicializado
            if (!isset($groupedRecords[$record->work_name])) {
                $groupedRecords[$record->work_name] = [];
            }
            if (!isset($groupedRecords[$record->work_name][$record->part_number])) {
                $groupedRecords[$record->work_name][$record->part_number] = [];
            }

            // Asignamos la cantidad más alta a la hora correspondiente
            $groupedRecords[$record->work_name][$record->part_number][$record->hour] = $record->max_quantity;
        }

        dd($groupedRecords, $startDate, $endDate);
    }
}
