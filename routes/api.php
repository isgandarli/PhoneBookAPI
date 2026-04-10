<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PhonebookController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\StructureController;
use App\Http\Controllers\StructureTypeController;
use Illuminate\Support\Facades\Route;

// Public routes (no JWT required)
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
});

Route::get('/phonebook', [PhonebookController::class, 'index']);

// Public read-only routes (no JWT required)
Route::apiResource('structure-types', StructureTypeController::class)->only(['index', 'show']);
Route::apiResource('positions', PositionController::class)->only(['index', 'show']);
Route::apiResource('structures', StructureController::class)->only(['index', 'show']);
Route::apiResource('employees', EmployeeController::class)->only(['index', 'show']);

// Protected routes (JWT required)
Route::middleware('jwt.auth')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    Route::apiResource('structure-types', StructureTypeController::class)->except(['index', 'show']);
    Route::apiResource('positions', PositionController::class)->except(['index', 'show']);
    Route::apiResource('structures', StructureController::class)->except(['index', 'show']);
    Route::apiResource('employees', EmployeeController::class)->except(['index', 'show']);
});
