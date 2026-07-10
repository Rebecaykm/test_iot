<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\LineController;
use App\Http\Controllers\LineStoppageController;
use App\Http\Controllers\LineStoppageRecordController;
use App\Http\Controllers\MaterialValidationController;
use App\Http\Controllers\PartNumberController;
use App\Http\Controllers\ProductionRecordController;
use App\Http\Controllers\ProductionTracking\HourlyShiftReportController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ScrapController;
use App\Http\Controllers\ScrapRecordController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TagTypeController;
use App\Http\Controllers\TypeLineStoppageController;
use App\Http\Controllers\TypeScrapController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisualAidController;
use App\Http\Controllers\WorkCenterController;
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

    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);
    // Route::resource('permissions', PermissionController::class)->except(['show']);
    Route::resource('roles', RoleController::class);
    Route::resource('areas', AreaController::class);
    Route::resource('lines', LineController::class);
    Route::resource('part-numbers', PartNumberController::class);
    Route::resource('work-centers', WorkCenterController::class);
    Route::resource('tags', TagController::class);
    Route::resource('part-numbers', PartNumberController::class);
    Route::resource('tag-types', TagTypeController::class);
    Route::resource('statuses', StatusController::class)->except('show');
    Route::resource('shifts', ShiftController::class)->except('show');
    Route::resource('clients', ClientController::class)->except('show');
    Route::resource('projects', ProjectController::class)->except('show');
    Route::resource('visual-aids', VisualAidController::class);
    Route::resource('type-scraps', TypeScrapController::class);
    Route::resource('scraps', ScrapController::class);
    Route::resource('scrap-records', ScrapRecordController::class);
    Route::resource('type-line-stoppages', TypeLineStoppageController::class);
    Route::resource('line-stoppages', LineStoppageController::class);
    Route::resource('line-stoppage-records', LineStoppageRecordController::class);
    Route::resource('production-records', ProductionRecordController::class);
    Route::get('production-records-summary', [ProductionRecordController::class, 'summary'])->name('production-records.summary');
    Route::get('production-records-summary/export', [ProductionRecordController::class, 'exportSummary'])->name('production-records.summary.export');

    Route::get('/production/export-pdf-form', [ProductionRecordController::class, 'showExportForm'])->name('production.export-pdf-form');
    Route::post('/production/export-pdf-filtered', [ProductionRecordController::class, 'exportProductionReportFiltered'])->name('production.export-pdf-filtered');
    Route::get('/production/export-pdf', [ProductionRecordController::class, 'exportProductionReport'])->name('production.export-pdf');

    // Escaneo de Tres Puntos
    Route::resource('material-validations', MaterialValidationController::class);
    Route::get('statistics', [MaterialValidationController::class, 'statistics'])->name('material-validations.statistics');

    Route::get('work-center-map', function () {
        return view('work-centers.work-center-map');
    })->name('work-center.map');
});

Route::get('show-visual-aids/{work_center}', [VisualAidController::class, 'showVisualAidForCurrentPart'])
    ->name('visual-aids.current-part');

Route::get('home', function () {
    return view('home');
})->name('home');

// Route::get('hourly-production-graph', function () {
//     return view('production-records.hourly-production-graph');
// });

// Revisar
// Route::get('get-production-records', function () {
//     return view('production-records.get-production-records');
// });

Route::get('production-dashboard/{workCenter}', function ($workCenter) {
    return view('production-dashboard', [
        'workCenter' => $workCenter,
    ]);
})->name('production-dashboard');

// Revisar
// Route::get('production-plan-summary', function () {
//     return view('production-plan-summary');
// });

// Press Routes
Route::get('press-production/{workCenter}', function ($workCenter) {
    return view('press-production', [
        'workCenter' => $workCenter,
    ]);
})->name('press-production');

// Guest Routes
Route::get('guest/work-center-map', function () {
    return view('work-center-map-view');
})->name('guest.work-center-map');

Route::get('guest/production-records/{workCenterId}', function ($workCenterId) {
    $workCenter = \App\Models\WorkCenter::findOrFail($workCenterId);

    return view('guest.production-record-view', [
        'workCenterId' => $workCenterId,
        'number' => $workCenter->number,
    ]);
})->name('guest.production-records');

Route::get('guest/work-center-dashboard', function () {
    return view('guest.work-center-dashboard');
});

// Production Tracking Status
Route::get('production-tracking/hourly-shift-report', [HourlyShiftReportController::class, '__invoke'])
    ->name('production-tracking.hourly-shift-report');
