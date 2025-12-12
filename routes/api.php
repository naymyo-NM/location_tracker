<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\SessionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);




// Route::middleware('auth:sanctum')->group(function () {
//     Route::post('/logout', [AuthController::class, 'logout']);
//     Route::apiResource('sessions', SessionController::class);
//     Route::apiResource('locations', LocationController::class);
// });

Route::middleware('auth:sanctum')->group(function () {

    // Sessions
    Route::get('sessions', [SessionController::class, 'index']);
    Route::post('sessions/start', [SessionController::class, 'start']);
    Route::post('sessions/{id}/stop', [SessionController::class, 'stop']);

    // Locations
    Route::post('locations', [LocationController::class, 'store']);
    Route::get('sessions/{id}/locations', [LocationController::class, 'sessionLocations']);
});


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
