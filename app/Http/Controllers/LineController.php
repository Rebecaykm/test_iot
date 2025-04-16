<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Line;
use App\Models\WorkCenter;
use Illuminate\Http\Request;

class LineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $lines = Line::query()->orderBy('name', 'asc')->paginate(10);

        return view('lines.index')->with('lines', $lines);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $areas = Area::query()->orderBy('name', 'asc')->get();

        return view('lines.create')->with('areas', $areas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:100|unique:lines,name',
            'description' => 'nullable|string|max:255',
            'area_id' => 'nullable|exists:areas,id'
        ], [
            'name.required' => 'El nombre de la línea es obligatorio.',
            'name.max' => 'El nombre no debe exceder los 100 caracteres.',
            'name.unique' => 'Este nombre de línea ya existe.',
            'area_id.exists' => 'El área seleccionada no es válida.'
        ]);

        try {
            $line = Line::create([
                'name' => $validatedData['name'],
                'description' => $validatedData['description'],
                'area_id' => $validatedData['area_id']
            ]);

            return redirect()->route('lines.index')
                ->with('success', 'Línea creada exitosamente!');
        } catch (\Exception $e) {
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
    public function destroy(Line $line)
    {
        try {
            $line->delete(); // Eliminación suave (soft delete)

            return redirect()->route('lines.index')
                ->with('success', 'Línea desactivada correctamente');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'No se pudo desactivar la línea: ' . $e->getMessage());
        }
    }
}
