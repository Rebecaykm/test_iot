<?php

namespace App\Http\Controllers;

use App\Models\TypeScrap;
use Illuminate\Http\Request;

class TypeScrapController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $typeScraps = TypeScrap::latest()->paginate(10);
        return view('type-scraps.index', compact('typeScraps'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('type-scraps.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:type_scraps,name',
            'description' => 'nullable|string',
        ]);

        TypeScrap::create($request->all());

        return redirect()->route('type-scraps.index')
            ->with('success', 'Tipo de scrap creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(TypeScrap $typeScrap)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TypeScrap $typeScrap)
    {
        return view('type-scraps.edit', compact('typeScrap'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TypeScrap $typeScrap)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:type_scraps,name,' . $typeScrap->id,
            'description' => 'nullable|string',
        ]);

        $typeScrap->update($request->all());

        return redirect()->route('type-scraps.index')
            ->with('success', 'Tipo de scrap actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TypeScrap $typeScrap)
    {
        $typeScrap->delete();

        return redirect()->route('type-scraps.index')
            ->with('success', 'Tipo de scrap eliminado exitosamente.');
    }
}
