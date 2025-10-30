<?php

namespace App\Http\Controllers;

use App\Models\TypeLineStoppage;
use Illuminate\Http\Request;

class TypeLineStoppageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');

        $typeLineStoppages = TypeLineStoppage::when($search, function ($query) use ($search) {
            return $query->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        })
            ->latest()
            ->paginate(10);

        return view('type-line-stoppages.index', compact('typeLineStoppages', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('type-line-stoppages.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:type_line_stoppages,name',
            'description' => 'nullable|string|max:500',
        ]);

        TypeLineStoppage::create($request->all());

        return redirect()->route('type-line-stoppages.index')
            ->with('success', 'Tipo de parada creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(TypeLineStoppage $typeLineStoppage)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TypeLineStoppage $typeLineStoppage)
    {
        return view('type-line-stoppages.edit', compact('typeLineStoppage'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TypeLineStoppage $typeLineStoppage)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:type_line_stoppages,name,' . $typeLineStoppage->id,
            'description' => 'nullable|string|max:500',
        ]);

        $typeLineStoppage->update($request->all());

        return redirect()->route('type-line-stoppages.index')
            ->with('success', 'Tipo de parada actualizado exitosamente.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TypeLineStoppage $typeLineStoppage)
    {
        // Verificar si tiene paradas relacionadas
        if ($typeLineStoppage->lineStoppages()->exists()) {
            return redirect()->route('type-line-stoppages.index')
                ->with('error', 'No se puede eliminar el tipo de parada porque tiene paradas relacionadas.');
        }

        $typeLineStoppage->delete();

        return redirect()->route('type-line-stoppages.index')
            ->with('success', 'Tipo de parada eliminado exitosamente.');
    }
}
