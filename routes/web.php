<?php

use App\Http\Controllers\PartNumberController;
use App\Http\Controllers\ProductionRecordController;
use App\Http\Controllers\VisualAidController;
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
    Route::resource('visual-aids', VisualAidController::class);
});

Route::get('show-visual-aids/{work_center}', [VisualAidController::class, 'showVisualAidForCurrentPart'])
    ->name('visual-aids.current-part');

Route::get('home', function () {
    return view('home');
})->name('home');

// Route::get('hourly-production-graph', function () {
//     return view('production-records.hourly-production-graph');
// });

Route::get('get-production-records', function () {
    return view('production-records.get-production-records');
});

Route::get('production-dashboard', function () {
    return view('production-dashboard');
})->name('production-dashboard');

Route::get('production-plan-summary', function () {
    return view('production-plan-summary');
});

// Press Routes
Route::get('press-production/{workCenter}', function ($workCenter) {
    return view('press-production', ['workCenter' => $workCenter]);
})->name('press-production');

// Test
Route::get('test', function () {
    return view('chart_test');
});
