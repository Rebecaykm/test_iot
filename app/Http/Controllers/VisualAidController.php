<?php

namespace App\Http\Controllers;

use App\Models\PartNumber;
use App\Models\ProductionRecord;
use App\Models\Shift;
use App\Models\VisualAid;
use App\Models\WorkCenter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VisualAidController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $partNumberId = $request->input('part_number');
        $partNumber = PartNumber::findOrFail($partNumberId);

        return view('visual-aids.create')->with('partNumber', $partNumber);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'part_number_id' => 'required|exists:part_numbers,id',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            'alt_text' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean'
        ]);

        $path = $request->file('image')->store('visual_aids', 'public');

        // La lógica de desactivación ahora está en el modelo (creating event)
        $visualAid = VisualAid::create([
            'part_number_id' => $validated['part_number_id'],
            'path' => $path,
            'alt_text' => $validated['alt_text'],
            'is_active' => $validated['is_active'] ?? false
        ]);

        return redirect()->route('part-numbers.edit', $validated['part_number_id'])
            ->with('success', 'Imagen agregada correctamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VisualAid $visualAid)
    {
        // Guardamos el part_number_id para usarlo después
        $partNumberId = $visualAid->part_number_id;
        $wasActive = $visualAid->is_active;

        // Eliminar archivo físico
        Storage::disk('public')->delete($visualAid->path);

        // Eliminar registro
        $visualAid->delete();

        // Si la imagen eliminada era la activa, buscar la última imagen y activarla
        if ($wasActive) {
            $lastImage = VisualAid::where('part_number_id', $partNumberId)
                ->latest() // Ordena por created_at desc (la más reciente primero)
                ->first();

            if ($lastImage) {
                $lastImage->update(['is_active' => true]);
            }
        }

        return redirect()->back()->with('success', 'Imagen eliminada');
    }

    public function showVisualAidForCurrentPart($work_center)
    {
        try {
            // Solo verificamos que el work center exista
            WorkCenter::where('name', $work_center)->firstOrFail();

            return view('visual-aids.show', [
                'work_center' => $work_center
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, "Centro de trabajo no encontrado");
        }
    }
}
