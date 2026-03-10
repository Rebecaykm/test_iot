<?php

namespace App\Http\Controllers;

use App\Models\PartNumber;
use App\Models\PartNumberDefaultScrap;
use App\Models\Scrap;
use Illuminate\Http\Request;

class PartNumberDefaultScrapController extends Controller
{
    public function create(PartNumber $partNumber)
    {
        $scraps = Scrap::orderBy('code')->get();

        return view('part-number-default-scraps.create', compact('partNumber', 'scraps'));
    }

    public function store(Request $request, PartNumber $partNumber)
    {
        $request->validate([
            'scrap_id'    => 'required|exists:scraps,id',
            'quantity'    => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        $partNumber->defaultScraps()->updateOrCreate(
            ['scrap_id' => $request->scrap_id],
            [
                'quantity'    => $request->quantity,
                'description' => $request->description,
            ]
        );

        return redirect()->route('part-numbers.edit', $partNumber)
            ->with('success', 'Scrap default guardado correctamente.');
    }

    public function destroy(PartNumberDefaultScrap $partNumberDefaultScrap)
    {
        $partNumberId = $partNumberDefaultScrap->part_number_id;
        $partNumberDefaultScrap->delete();

        return redirect()->route('part-numbers.edit', $partNumberId)
            ->with('success', 'Scrap default eliminado correctamente.');
    }
}
