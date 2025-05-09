<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\LineController;
use App\Http\Controllers\PartNumberController;
use App\Http\Controllers\ProductionRecordController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TagTypeController;
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
    Route::resource('visual-aids', VisualAidController::class);
    Route::resource('production-records', ProductionRecordController::class);

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
        'workCenter' => $workCenter
    ]);
})->name('production-dashboard');


// Revisar
// Route::get('production-plan-summary', function () {
//     return view('production-plan-summary');
// });

// Press Routes
Route::get('press-production/{workCenter}', function ($workCenter) {
    return view('press-production', [
        'workCenter' => $workCenter
    ]);
})->name('press-production');


// Guest Routes
Route::get('guest/work-center-map', function () {
    return view('work-center-map-view');
})->name('guest.work-center-map');

Route::get('guest/production-records/{workCenterId}', function ($workCenterId) {
    return view('guest.production-record-view', [
        'workCenterId' => $workCenterId,
    ]);
})->name('guest.production-records');
