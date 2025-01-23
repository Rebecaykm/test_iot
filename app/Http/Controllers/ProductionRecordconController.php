<?php

namespace App\Http\Controllers;

use App\Models\ProductionRecord;
use Illuminate\Http\Request;

class ProductionRecordconController extends Controller
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
}
