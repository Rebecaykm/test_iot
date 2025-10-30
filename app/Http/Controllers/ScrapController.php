<?php

namespace App\Http\Controllers;

use App\Models\Scrap;
use App\Models\TypeScrap;
use Illuminate\Http\Request;

class ScrapController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $scraps = Scrap::with('typeScrap')->latest()->paginate(10);
        return view('scraps.index', compact('scraps'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $typeScraps = TypeScrap::all();
        return view('scraps.create', compact('typeScraps'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:255|unique:scraps,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type_scrap_id' => 'required|exists:type_scraps,id',
        ]);

        Scrap::create($request->all());

        return redirect()->route('scraps.index')
            ->with('success', 'Scrap creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Scrap $scrap)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Scrap $scrap)
    {
        $typeScraps = TypeScrap::all();
        return view('scraps.edit', compact('scrap', 'typeScraps'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Scrap $scrap)
    {
        $request->validate([
            'code' => 'required|string|max:255|unique:scraps,code,' . $scrap->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type_scrap_id' => 'required|exists:type_scraps,id',
        ]);

        $scrap->update($request->all());

        return redirect()->route('scraps.index')
            ->with('success', 'Scrap actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Scrap $scrap)
    {
        $scrap->delete();

        return redirect()->route('scraps.index')
            ->with('success', 'Scrap eliminado exitosamente.');
    }
}
