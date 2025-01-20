<?php

use App\Http\Controllers\PartNumberController;
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
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

Route::get('test', function () {
    return view('test');
});

Route::resource('part-numbers', PartNumberController::class);
Route::resource('work-centers', WorkCenterController::class);
