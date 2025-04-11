<?php

namespace App\Http\Controllers;

use App\Jobs\GetPartNumberJob;
use App\Models\PartNumber;
use App\Models\VisualAid;
use Illuminate\Http\Request;

class PartNumberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = PartNumber::query()->orderBy('created_at', 'desc');

        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;

            $query->where(function ($q) use ($searchTerm) {
                $q->where('number', 'like', "%{$searchTerm}%")
                    ->orWhere('name', 'like', "%{$searchTerm}%")
                    ->orWhereHas('workCenter', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', "%{$searchTerm}%");
                    });
            });
        }

        $partNumbers = $query->paginate(10)->appends($request->query());

        return view('part-numbers.index', ['partNumbers' => $partNumbers]);
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
    public function edit(PartNumber $partNumber)
    {
        $visualAids = VisualAid::query()->where('part_number_id', $partNumber->id)->get();

        return view('part-numbers.edit')->with('partNumber', $partNumber)->with('visualAids', $visualAids);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PartNumber $partNumber  )
    {
        $efficiency = $request->input("efficiency");

        $partNumber->update(['efficiency' => $efficiency]);

        return redirect()->back()->with('success', 'Eficiencia Actualizada');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
