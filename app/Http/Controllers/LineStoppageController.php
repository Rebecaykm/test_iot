<?php

namespace App\Http\Controllers;

use App\Models\LineStoppage;
use App\Models\TypeLineStoppage;
use Illuminate\Http\Request;

class LineStoppageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');

        $lineStoppages = LineStoppage::with('typeLineStoppage')
            ->when($search, function ($query) use ($search) {
                return $query->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('typeLineStoppage', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->paginate(10);

        return view('line-stoppages.index', compact('lineStoppages', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $typeLineStoppages = TypeLineStoppage::all();
        return view('line-stoppages.create', compact('typeLineStoppages'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:line_stoppages,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'type_line_stoppage_id' => 'required|exists:type_line_stoppages,id',
        ]);

        LineStoppage::create($request->all());

        return redirect()->route('line-stoppages.index')
            ->with('success', 'Parada de línea creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(LineStoppage $lineStoppage)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LineStoppage $lineStoppage)
    {
        $typeLineStoppages = TypeLineStoppage::all();
        return view('line-stoppages.edit', compact('lineStoppage', 'typeLineStoppages'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LineStoppage $lineStoppage)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:line_stoppages,code,' . $lineStoppage->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'type_line_stoppage_id' => 'required|exists:type_line_stoppages,id',
        ]);

        $lineStoppage->update($request->all());

        return redirect()->route('line-stoppages.index')
            ->with('success', 'Parada de línea actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LineStoppage $lineStoppage)
    {
        // Verificar si tiene registros relacionados
        if ($lineStoppage->lineStoppageRecord()->exists()) {
            return redirect()->route('line-stoppages.index')
                ->with('error', 'No se puede eliminar la parada de línea porque tiene registros relacionados.');
        }

        $lineStoppage->delete();

        return redirect()->route('line-stoppages.index')
            ->with('success', 'Parada de línea eliminada exitosamente.');
    }
}
