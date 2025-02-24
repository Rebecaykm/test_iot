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

Route::get('home', function () {
    return view('home');
})->name('home');

Route::get('get-production-records', function () {
    return view('production-records.get-production-records');
});

Route::get('hourly-production-graph', function () {
    return view('production-records.hourly-production-graph');
});
// Route::get('production-records', [ProductionRecordController::class, 'getProductionRecords'])->name('production-records.production-records');
// Route::get('hourly-production-graph', [ProductionRecordController::class, 'getHourlyProductionGraph'])->name('production-records.hourly-production-graph');

Route::get('production-dashboard', function () {
    return view('production-dashboard');
})->name('production-dashboard');

Route::get('production-plan-summary', function () {
    return view('production-plan-summary');
});

Route::get('chart', function () {
    return view('chart');
});

Route::get('testeo', [ProductionRecordController::class, 'index']);
