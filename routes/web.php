<?php

use App\Http\Controllers\PartNumberController;
use App\Http\Controllers\ProductionRecordconController;
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
});

Route::get('chart', [ProductionRecordconController::class, 'chart'])->name('chart');
