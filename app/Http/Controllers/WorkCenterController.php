<?php

namespace App\Http\Controllers;

use App\Jobs\GetWorkCenterJob;
use App\Models\WorkCenter;
use Illuminate\Http\Request;

use function Laravel\Prompts\search;

class WorkCenterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // $search =
        $workCenters = WorkCenter::query()->orderBy('created_at', 'desc')->paginate(10);

        return view('work-centers.index', ['workCenters' => $workCenters]);
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
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
