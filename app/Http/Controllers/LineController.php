<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Line;
use App\Models\WorkCenter;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class LineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Line::query()->orderBy('name', 'asc');

        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;

            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%");
            });
        }

        $lines = $query->paginate(10)->appends($request->query());

        return view('lines.index')->with('lines', $lines);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $areas = Area::query()->orderBy('name', 'asc')->get();
        $workCenters = WorkCenter::query()
            ->whereNull('line_id') // Solo estaciones no asignadas
            ->orderBy('number', 'asc')
            ->get();

        return view('lines.create', compact('areas', 'workCenters'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:lines,name'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-F]{6}$/i'],
            'description' => ['nullable', 'string', 'max:255'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'work_centers' => ['required', 'array', 'min:1'],
            'work_centers.*' => [
                'exists:work_centers,id',
                Rule::exists('work_centers', 'id')->whereNull('line_id')
            ]
        ], [
            'name.required' => 'El nombre de la línea es obligatorio.',
            'name.max' => 'El nombre no debe exceder los 100 caracteres.',
            'name.unique' => 'Este nombre de línea ya existe.',
            'area_id.exists' => 'El área seleccionada no es válida.',
            'work_centers.required' => 'Debe seleccionar al menos una estación de trabajo.',
            'work_centers.min' => 'Debe seleccionar al menos una estación de trabajo.',
            'work_centers.*.exists' => 'Una o más estaciones seleccionadas no están disponibles (pueden estar asignadas a otra línea).',
            'color.regex' => 'El color debe ser un código hexadecimal válido (ej: #FFFFFF).'
        ]);

        // Usar transacción para asegurar integridad de datos
        DB::beginTransaction();

        try {
            // Crear la línea
            $line = Line::create([
                'name' => $validatedData['name'],
                'color' => $validatedData['color'] ?? null, // Mejor manejo de null
                'description' => $validatedData['description'] ?? null,
                'area_id' => $validatedData['area_id'] ?? null
            ]);

            // Actualizar work centers (sintaxis corregida)
            WorkCenter::whereIn('id', $validatedData['work_centers'])
                ->update(['line_id' => $line->id]);

            DB::commit();

            return redirect()->route('lines.index')
                ->with('success', 'Línea creada exitosamente!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Error al crear la línea: ' . $e->getMessage());
        }
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
    public function edit(Line $line)
    {
        $areas = Area::orderBy('name', 'asc')->get();
        $workCenters = WorkCenter::orderBy('number', 'asc')->get();

        return view('lines.edit', compact('line', 'areas', 'workCenters'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Line $line)
    {
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('lines')->ignore($line->id)],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-F]{6}$/i'],
            'description' => ['nullable', 'string', 'max:255'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'work_centers' => ['required', 'array', 'min:1'],
            'work_centers.*' => [
                'exists:work_centers,id',
                Rule::exists('work_centers', 'id')->where(function ($query) use ($line) {
                    $query->whereNull('line_id')->orWhere('line_id', $line->id);
                })
            ]
        ], [
            'name.required' => 'El nombre de la línea es obligatorio.',
            'name.max' => 'El nombre no debe exceder los 100 caracteres.',
            'name.unique' => 'Este nombre de línea ya existe.',
            'area_id.exists' => 'El área seleccionada no es válida.',
            'work_centers.required' => 'Debe seleccionar al menos una estación de trabajo.',
            'work_centers.min' => 'Debe seleccionar al menos una estación de trabajo.',
            'work_centers.*.exists' => 'Una o más estaciones seleccionadas no están disponibles (pueden estar asignadas a otra línea).',
            'color.regex' => 'El color debe ser un código hexadecimal válido (ej: #FFFFFF).'
        ]);

        DB::beginTransaction();

        try {
            // Actualizar la línea
            $line->update([
                'name' => $validatedData['name'],
                'color' => $validatedData['color'] ?? null,
                'description' => $validatedData['description'] ?? null,
                'area_id' => $validatedData['area_id'] ?? null
            ]);

            // Primero, desasignar todas las estaciones de esta línea
            WorkCenter::where('line_id', $line->id)
                ->whereNotIn('id', $validatedData['work_centers'])
                ->update(['line_id' => null]);

            // Luego asignar las nuevas estaciones
            WorkCenter::whereIn('id', $validatedData['work_centers'])
                ->update(['line_id' => $line->id]);

            DB::commit();

            return redirect()->route('lines.index')
                ->with('success', 'Línea actualizada exitosamente!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Error al actualizar la línea: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Line $line)
    {
        DB::beginTransaction();

        try {
            // 1. Primero liberar todas las estaciones de trabajo asociadas
            WorkCenter::where('line_id', $line->id)
                ->update(['line_id' => null]);

            // 2. Luego eliminar la línea (soft delete)
            $line->delete();

            DB::commit();

            return redirect()->route('lines.index')
                ->with('success', 'Línea eliminada correctamente. Todas sus estaciones de trabajo han sido liberadas.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al eliminar línea: ' . $e->getMessage(), [
                'line_id' => $line->id,
                'user_id' => Auth::id()
            ]);

            return redirect()->back()
                ->with('error', 'No se pudo eliminar la línea: ' . $e->getMessage());
        }
    }
}
