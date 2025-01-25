<?php

use App\Http\Controllers\PartNumberController;
use App\Http\Controllers\ProductionRecordController;
use App\Http\Controllers\WorkCenterController;
use App\Models\ProductionRecord;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/home', function () {
        return view('home');
    })->name('home');

    Route::resource('part-numbers', PartNumberController::class);
    Route::resource('work-centers', WorkCenterController::class);
    Route::resource('production-records', ProductionRecordController::class);
});

Route::get('chart', function () {
    return view('chart_test');
})->name('chart');
Route::get('show-plan-production', [ProductionRecordController::class, 'showPlanAndProduction'])->name('production-records.plan-production');
Route::get('get-hourly', [ProductionRecordController::class, 'getHourlyProductionRecord'])->name('production-records.get-hourly');
