<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\TrackingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', fn (Request $request) => $request->user());

    Route::get('sessions', [SessionController::class, 'index']);
    Route::post('sessions/start', [SessionController::class, 'start']);
    Route::post('sessions/{id}/stop', [SessionController::class, 'stop']);

    Route::get('sessions/{id}/locations', [LocationController::class, 'sessionLocations']);

    Route::post('trackings', [TrackingController::class, 'storeTracking']);
    Route::post('trackings/batch', [TrackingController::class, 'storeBatch']);
    Route::get('sessions/{id}/points', [TrackingController::class, 'sessionPoints']);
});
