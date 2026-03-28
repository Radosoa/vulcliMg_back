<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VulnerabilityController;

/*
|--------------------------------------------------------------------------
| Authentication Routes (Public)
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Protected Routes (Require Authentication)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Vulnerability routes
    Route::get('/regions', [VulnerabilityController::class, 'getRegions']);
    Route::get('/bio-weights', [VulnerabilityController::class, 'getBioWeights']);
    Route::put('/bio-weights', [VulnerabilityController::class, 'updateBioWeights']);
    Route::post('/vulnerability-recalculate', [VulnerabilityController::class, 'recalculateVulnerabilityIndex']);
    Route::get('/vulnerability-stats', [VulnerabilityController::class, 'getVulnerabilityStats']);
});