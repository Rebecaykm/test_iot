<?php

namespace App\Http\Controllers;

use App\Models\LineStoppage;
use App\Models\LineStoppageRecord;
use App\Models\WorkCenter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LineStoppageRecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');

        $lineStoppageRecords = LineStoppageRecord::with(['lineStoppage', 'workCenter'])
            ->when($search, function ($query) use ($search) {
                return $query->whereHas('lineStoppage', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                })->orWhereHas('workCenter', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10);

        return view('line-stoppage-records.index', compact('lineStoppageRecords', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $lineStoppages = LineStoppage::all();
        $workCenters = Auth::user()->workCenters()->orderBy('name')->get();

        // Obtener la ruta de origen del parámetro o de la sesión
        $source = $request->get('source', session('previous_route', 'line-stoppage-records.index'));

        // Guardar en sesión para uso posterior
        session(['stoppage_create_source' => $source]);

        return view('line-stoppage-records.create', compact('lineStoppages', 'workCenters', 'source'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'line_stoppage_id' => 'required|exists:line_stoppages,id',
            'work_center_id' => 'required|exists:work_centers,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'minutes_stoppage' => 'required|integer|min:1',
        ]);

        // Convertir el formato de fecha para SQL Server
        $data = $request->all();
        $data['start_time'] = Carbon::parse($request->start_time)->format('Y-m-d H:i:s');
        $data['end_time'] = Carbon::parse($request->end_time)->format('Y-m-d H:i:s');

        LineStoppageRecord::create($data);

        // Redirigir según la fuente
        $redirectRoute = session('stoppage_create_source', 'line-stoppage-records.index');
        session()->forget('stoppage_create_source');

        return redirect()->route($redirectRoute)
            ->with('success', 'Registro de parada creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(LineStoppageRecord $lineStoppageRecord)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LineStoppageRecord $lineStoppageRecord)
    {
        $lineStoppages = LineStoppage::all();
        $workCenters = Auth::user()->workCenters()->orderBy('name')->get();

        // Guardar la ruta actual para el botón de cancelar
        session(['stoppage_edit_source' => url()->previous()]);

        return view('line-stoppage-records.edit', compact('lineStoppageRecord', 'lineStoppages', 'workCenters'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LineStoppageRecord $lineStoppageRecord)
    {
        $request->validate([
            'line_stoppage_id' => 'required|exists:line_stoppages,id',
            'work_center_id' => 'required|exists:work_centers,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ]);

        // Convertir y asegurar los tipos de datos
        $data = $request->all();
        $data['start_time'] = Carbon::parse($request->start_time)->format('Y-m-d H:i:s');
        $data['end_time'] = Carbon::parse($request->end_time)->format('Y-m-d H:i:s');
        $data['minutes_stoppage'] = (int) $request->minutes_stoppage;

        $lineStoppageRecord->update($data);

        // Redirigir según la fuente guardada
        $redirectRoute = session('stoppage_edit_source', 'line-stoppage-records.index');
        session()->forget('stoppage_edit_source');

        return redirect()->to($redirectRoute)
            ->with('success', 'Registro de parada actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LineStoppageRecord $lineStoppageRecord)
    {
        $lineStoppageRecord->delete();

        return redirect()->route('line-stoppage-records.index')
            ->with('success', 'Registro de parada eliminado exitosamente.');
    }
}
