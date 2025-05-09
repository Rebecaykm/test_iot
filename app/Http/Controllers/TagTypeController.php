<?php

namespace App\Http\Controllers;

use App\Models\TagType;
use Illuminate\Http\Request;

class TagTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $tagTypes = TagType::query()
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->paginate(10);

        return view('tag-types.index')->with('tagTypes', $tagTypes);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('tag-types.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        TagType::create($validated);

        return redirect()->route('tag-types.index')->with('success', 'Tipo de etiqueta creado correctamente.');
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
    public function edit(TagType $tagType)
    {
        return view('tag-types.edit', compact('tagType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TagType $tagType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $tagType->update($validated);

        return redirect()->route('tag-types.index')->with('success', 'Tipo de etiqueta actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TagType $tagType)
    {
        $tagType->delete();

        return redirect()->route('tag-types.index')->with('success', 'Tipo de etiqueta eliminado correctamente.');
    }
}
