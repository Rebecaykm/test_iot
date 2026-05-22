<?php

namespace App\Http\Controllers;

use App\Jobs\GetWorkCenterJob;
use App\Models\Line;
use App\Models\ProductionRecord;
use App\Models\Shift;
use App\Models\Tag;
use App\Models\WorkCenter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Queue\Worker;

use function Laravel\Prompts\search;

class WorkCenterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $workCenters = WorkCenter::query()
            ->with(['line', 'line.area'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('number', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhereHas('line', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('work-centers.index', compact('workCenters', 'search'));
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
    public function show(WorkCenter $workCenter)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(WorkCenter $workCenter)
    {
        $tags = Tag::query()->where('work_center_id', $workCenter->id)->orderBy('created_at', 'desc')->get();

        return view('work-centers.edit')->with('workCenter', $workCenter)->with('tags', $tags);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WorkCenter $workCenter)
    {
        // Validar solo el campo IP
        $validated = $request->validate([
            'ip' => 'nullable|string|max:45'
        ]);

        // Actualizar solo el campo IP
        $workCenter->update([
            'ip' => $validated['ip']
        ]);

        return redirect()->route('work-centers.edit', $workCenter->id)
            ->with('success', 'Dirección IP actualizada exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
