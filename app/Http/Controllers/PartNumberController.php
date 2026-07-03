<?php

namespace App\Http\Controllers;

use App\Imports\ProductionOrdersImport;
use App\Jobs\GetPartNumberJob;
use App\Models\PartNumber;
use App\Models\VisualAid;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PartNumberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = PartNumber::with(['workCenter.line', 'itemClass'])
            ->orderBy('created_at', 'desc');

        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;

            $query->where(function ($q) use ($searchTerm) {
                $q->where('number', 'like', "%{$searchTerm}%")
                    ->orWhere('name', 'like', "%{$searchTerm}%")
                    ->orWhereHas('workCenter', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', "%{$searchTerm}%")
                            ->orWhere('number', 'like', "%{$searchTerm}%")
                            ->orWhereHas('line', function ($q) use ($searchTerm) {
                                $q->where('name', 'like', "%{$searchTerm}%")
                                    ->orWhere('color', 'like', "%{$searchTerm}%")
                                    ->orWhere('description', 'like', "%{$searchTerm}%");
                            });
                    })
                    ->orWhereHas('itemClass', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', "%{$searchTerm}%")
                            ->orWhere('abbreviation', 'like', "%{$searchTerm}%");
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

        // Obtener otros part numbers del mismo work center para mostrar conflictos de orden
        $partNumbersInSameWorkCenter = PartNumber::where('work_center_id', $partNumber->work_center_id)
            ->where('id', '!=', $partNumber->id)
            ->whereNotNull('production_order')
            ->orderBy('production_order', 'asc')
            ->get(['id', 'number', 'production_order']);

        return view('part-numbers.edit')
            ->with('partNumber', $partNumber)
            ->with('visualAids', $visualAids)
            ->with('partNumbersInSameWorkCenter', $partNumbersInSameWorkCenter);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PartNumber $partNumber)
    {
        // Validación de los campos
        $request->validate([
            'efficiency' => 'nullable|numeric|min:0|max:100',
            'production_order' => 'nullable|integer|min:0'
        ]);

        $efficiency = $request->input("efficiency");
        $productionOrder = $request->input("production_order");

        // Preparar los datos a actualizar
        $dataToUpdate = [];

        if ($request->has('efficiency')) {
            if ($efficiency !== null && $efficiency !== '') {
                $dataToUpdate['efficiency'] = $efficiency;
            } else {
                // Si está vacío, establecer como null
                $dataToUpdate['efficiency'] = null;
            }
        }

        // Manejar el campo production_order
        // Verificar si el campo fue enviado en la request (incluso si está vacío)
        if ($request->has('production_order')) {
            if ($productionOrder === null || $productionOrder === '' || $productionOrder === 0) {
                // Si está vacío, es null o es 0, establecer como null
                $dataToUpdate['production_order'] = null;
            } else {
                // Verificar si ya existe otro part number con el mismo orden en el mismo work center
                $existingPartNumber = PartNumber::where('work_center_id', $partNumber->work_center_id)
                    ->where('production_order', $productionOrder)
                    ->where('id', '!=', $partNumber->id)
                    ->first();

                if ($existingPartNumber) {
                    return redirect()->back()->withErrors([
                        'production_order' => "Ya existe otro número de parte ({$existingPartNumber->number}) con el orden {$productionOrder} en esta estación."
                    ])->withInput();
                }

                $dataToUpdate['production_order'] = $productionOrder;
            }
        }

        // Actualizar solo si hay datos para actualizar
        if (!empty($dataToUpdate)) {
            $partNumber->update($dataToUpdate);
        }

        // Mensaje de éxito personalizado
        $messages = [];
        if (isset($dataToUpdate['efficiency'])) {
            $messages[] = 'eficiencia';
        }
        if (array_key_exists('production_order', $dataToUpdate)) {
            if ($dataToUpdate['production_order'] === null) {
                $messages[] = 'orden de producción (removida)';
            } else {
                $messages[] = 'orden de producción';
            }
        }

        $successMessage = !empty($messages) ? 'Actualizado: ' . implode(' y ', $messages) : 'No se realizaron cambios.';

        return redirect()->back()->with('success', $successMessage);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Importa órdenes de producción desde un archivo Excel/CSV con encabezados
     * NO PARTE y ORDEN, actualizando production_order de cada número de parte.
     */
    public function importProductionOrders(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt,ods',
        ], [
            'file.required' => 'Selecciona un archivo para importar.',
            'file.mimes' => 'El archivo debe ser Excel o CSV (xlsx, xls, csv, ods).',
        ]);

        $import = new ProductionOrdersImport();

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            return redirect()->route('part-numbers.index')
                ->with('error', 'No se pudo procesar el archivo: ' . $e->getMessage());
        }

        if (!$import->headersValid) {
            return redirect()->route('part-numbers.index')
                ->with('error', 'El archivo debe contener los encabezados "NO PARTE" y "ORDEN".');
        }

        $warnings = [];

        if (!empty($import->notFound)) {
            $notFound = array_values(array_unique($import->notFound));
            $shown = array_slice($notFound, 0, 15);
            $rest = count($notFound) - count($shown);
            $warnings[] = 'Números de parte no encontrados (o obsoletos): ' . implode(', ', $shown)
                . ($rest > 0 ? " y {$rest} más." : '.');
        }

        if (!empty($import->invalidRows)) {
            $warnings[] = 'Filas con datos vacíos o inválidos: ' . implode(', ', $import->invalidRows) . '.';
        }

        if ($import->updated === 0 && empty($warnings)) {
            return redirect()->route('part-numbers.index')
                ->with('error', 'El archivo no contiene filas para procesar.');
        }

        return redirect()->route('part-numbers.index')
            ->with('success', "Órdenes de producción actualizadas: {$import->updated}.")
            ->with('import_warnings', $warnings);
    }

    /**
     * Método para obtener part numbers ordenados por estación (para AJAX si lo necesitas)
     */
    public function getPartNumbersByWorkCenter(Request $request)
    {
        $workCenterId = $request->input('work_center_id');

        $partNumbers = PartNumber::where('work_center_id', $workCenterId)
            ->orderBy('production_order', 'asc')
            ->get(['id', 'number', 'production_order']);

        return response()->json($partNumbers);
    }
}
