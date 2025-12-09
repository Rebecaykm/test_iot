<?php

namespace App\Http\Controllers;

use App\Models\PartNumber;
use App\Models\Scrap;
use App\Models\ScrapRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScrapRecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userWorkCenterIds = Auth::user()->workCenters->pluck('id');

        $scrapRecords = ScrapRecord::with(['scrap', 'partNumber'])
            ->whereHas('partNumber', function ($query) use ($userWorkCenterIds) {
                $query->whereIn('work_center_id', $userWorkCenterIds);
            })
            ->latest()
            ->paginate(10);

        return view('scrap-records.index', compact('scrapRecords'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $userWorkCenterIds = Auth::user()->workCenters->pluck('id');
        $scraps = Scrap::all();

        $partNumbers = PartNumber::whereIn('work_center_id', $userWorkCenterIds)
            ->orderBy('number', 'asc')
            ->get();

        // Obtener la ruta de origen del parámetro o de la sesión
        $source = $request->get('source', session('previous_route', 'scrap-records.index'));

        // Guardar en sesión para uso posterior
        session(['scrap_create_source' => $source]);

        return view('scrap-records.create', compact('scraps', 'partNumbers', 'source'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'part_number_id' => 'required|exists:part_numbers,id',
            'scrap_id' => 'required|exists:scraps,id',
            'quantity' => 'required|integer|min:0',
        ]);

        ScrapRecord::create($request->all());

        // Redirigir según la fuente
        $redirectRoute = session('scrap_create_source', 'scrap-records.index');
        session()->forget('scrap_create_source');

        return redirect()->route($redirectRoute)
            ->with('success', 'Registro de scrap creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ScrapRecord $scrapRecord)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ScrapRecord $scrapRecord)
    {
        $scraps = Scrap::all();
        $partNumbers = PartNumber::all();

        // Guardar la ruta actual para el botón de cancelar
        session(['scrap_edit_source' => url()->previous()]);

        return view('scrap-records.edit', compact('scrapRecord', 'scraps', 'partNumbers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ScrapRecord $scrapRecord)
    {
        $request->validate([
            'part_number_id' => 'required|exists:part_numbers,id',
            'scrap_id' => 'required|exists:scraps,id',
            'quantity' => 'required|integer|min:0',
        ]);

        $userWorkCenterIds = Auth::user()->workCenters->pluck('id');
        $allowedPartNumberIds = PartNumber::whereIn('work_center_id', $userWorkCenterIds)->pluck('id');

        if (!$allowedPartNumberIds->contains($request->input('part_number_id'))) {
            return redirect()->back()->withErrors(['part_number_id' => 'No tienes permiso para usar este número de parte.']);
        }

        $scrapRecord->update($request->only(['part_number_id', 'scrap_id', 'quantity']));

        // Redirigir según la fuente guardada
        $redirectRoute = session('scrap_edit_source', 'scrap-records.index');
        session()->forget('scrap_edit_source');

        return redirect()->to($redirectRoute)
            ->with('success', 'Registro de scrap actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ScrapRecord $scrapRecord)
    {
        $scrapRecord->delete();

        return redirect()->route('scrap-records.index')
            ->with('success', 'Registro de scrap eliminado exitosamente.');
    }
}
