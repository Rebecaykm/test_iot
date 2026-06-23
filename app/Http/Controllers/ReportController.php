<?php

namespace App\Http\Controllers;

use App\Exports\MdiReportExport;
use App\Models\PartNumber;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    /**
     * Vista principal con los reportes generales disponibles (mosaicos).
     */
    public function index()
    {
        return view('reports.index');
    }

    /**
     * Descarga el Reporte de MDI en Excel.
     *
     * Incluye únicamente los números de parte que tienen un MDI asociado
     * (atributo 'mid' no vacío) y que pertenecen a las líneas asociadas al
     * usuario autenticado.
     */
    public function exportMdi()
    {
        $lineIds = Auth::user()->lines()->pluck('lines.id')->toArray();

        $parts = PartNumber::query()
            ->select([
                'areas.name AS area_name',
                'lines.name AS line_name',
                'work_centers.number AS work_number',
                'work_centers.name AS work_name',
                'part_numbers.number AS part_number',
                'part_numbers.name AS part_name',
                'attributes.value AS mid',
            ])
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('lines', 'work_centers.line_id', '=', 'lines.id')
            ->join('areas', 'lines.area_id', '=', 'areas.id')
            ->join('attributes', function ($join) {
                $join->on('attributes.attributable_id', '=', 'part_numbers.id')
                    ->where('attributes.attributable_type', '=', PartNumber::class)
                    ->where('attributes.key', '=', 'mid');
            })
            ->whereIn('lines.id', $lineIds)
            ->whereNotNull('attributes.value')
            ->where('attributes.value', '!=', '')
            ->orderBy('areas.name')
            ->orderBy('lines.name')
            ->orderBy('work_centers.number')
            ->orderBy('part_numbers.number')
            ->get();

        $filename = 'MDI_' . Carbon::now()->format('YmdHis') . '.xlsx';

        return Excel::download(new MdiReportExport($parts), $filename);
    }
}
