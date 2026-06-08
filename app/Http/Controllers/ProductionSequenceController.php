<?php

namespace App\Http\Controllers;

use App\Models\ProductionSequence;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductionSequenceController extends Controller
{
    /**
     * Mostrar el listado de secuencias de producción.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $date   = $request->input('date');
        $shift  = $request->input('shift');

        $workCenterNames = Auth::user()->workCenters->pluck('name')->toArray();

        $shifts = Shift::orderBy('start_time')->get();

        $sequences = ProductionSequence::query()
            ->select([
                'production_sequences.id AS sequence_id',
                'part_numbers.number AS part_number',
                'part_numbers.name AS part_name',
                'production_records.planned_date AS planned_date',
                'shifts.abbreviation AS shift_name',
                'production_sequences.sequence_number AS sequence_number',
                'production_sequences.quantity AS quantity',
                'production_sequences.is_processed AS is_processed',
                'production_sequences.updated_at AS updated_at',
            ])
            ->join('production_records', 'production_sequences.production_record_id', '=', 'production_records.id')
            ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->leftJoin('shifts', 'production_records.shift_id', '=', 'shifts.id')
            ->whereIn('work_centers.name', $workCenterNames)
            ->when($date, function ($query, $date) {
                return $query->whereDate('production_records.planned_date', $date);
            })
            ->when($shift, function ($query, $shift) {
                return $query->where('shifts.abbreviation', $shift);
            })
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('part_numbers.number', 'like', "%{$search}%")
                        ->orWhere('part_numbers.name', 'like', "%{$search}%");
                });
            })
            ->orderBy('production_records.planned_date', 'desc')
            ->orderBy('shifts.abbreviation', 'asc')
            ->orderBy('part_numbers.number', 'asc')
            ->orderBy('production_sequences.sequence_number', 'asc')
            ->paginate(15)
            ->withQueryString();

        return view('production-sequences.index', compact('sequences', 'shifts'));
    }

    /**
     * Mostrar el formulario para crear una nueva secuencia.
     */
    public function create()
    {
        //
    }

    /**
     * Almacenar una nueva secuencia en la base de datos.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Mostrar una secuencia específica.
     */
    public function show(ProductionSequence $productionSequence)
    {
        //
    }

    /**
     * Mostrar el formulario para editar una secuencia.
     */
    public function edit(ProductionSequence $productionSequence)
    {
        //
    }

    /**
     * Actualizar una secuencia en la base de datos.
     */
    public function update(Request $request, ProductionSequence $productionSequence)
    {
        //
    }

    /**
     * Eliminar una secuencia de la base de datos.
     */
    public function destroy(ProductionSequence $productionSequence)
    {
        //
    }
}
