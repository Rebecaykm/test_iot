<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\TagType;
use App\Models\WorkCenter;
use Illuminate\Http\Request;

class TagController extends Controller
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
    public function create(Request $request)
    {
        $workCenter = WorkCenter::query()->find($request->work_center);
        $tagTypes = TagType::query()->orderBy('name', 'asc')->get();

        return view('tags.create')->with('workCenter', $workCenter)->with('tagTypes', $tagTypes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'address' => 'required|string|max:255',
            'long' => 'required|numeric',
            'value' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'tag_type_id' => 'required|exists:tag_types,id',
            'work_center_id' => 'required|exists:work_centers,id'
        ]);

        Tag::create([
            'address' => $validated['address'],
            'long' => $validated['long'],
            'value' => $validated['value'],
            'description' => $validated['description'],
            'work_center_id' => $validated['work_center_id'],
            'tag_type_id' => $validated['tag_type_id'],
        ]);

        return redirect()->route('work-centers.edit', $validated['work_center_id'])
            ->with('success', 'Tag creado exitosamente');
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
    public function edit(Tag $tag)
    {
        $workCenter = $tag->workCenter;
        $tagTypes = TagType::orderBy('name', 'asc')->get();

        return view('tags.edit', compact('tag', 'workCenter', 'tagTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Tag $tag)
    {
        $validated = $request->validate([
            'address' => 'required|string|max:255',
            'long' => 'nullable|numeric',
            'value' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'tag_type_id' => 'required|exists:tag_types,id',
            'work_center_id' => 'required|exists:work_centers,id'
        ]);

        $tag->update($validated);

        return redirect()->route('work-centers.edit', $validated['work_center_id'])
            ->with('success', 'Tag actualizado exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tag $tag)
    {
        $workCenterId = $tag->work_center_id;
        $tag->delete();

        return redirect()->route('work-centers.edit', $workCenterId)
            ->with('success', 'Tag eliminado exitosamente');
    }
}
