<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MaterialValidationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::post('/material-validations', [MaterialValidationController::class, 'store']);
    Route::get('/material-validations', [MaterialValidationController::class, 'index']);
    Route::get('/material-validations/statistics', [MaterialValidationController::class, 'statistics']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/test-connection', function () {
    return response()->json(['status' => 'success']);
});

Route::get('/health', function () {
    return response()->json(['status' => 'ok'], 200);
});
