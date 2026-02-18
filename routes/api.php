<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\Api\AuthController;

// Auth (عامة)
Route::post('/auth/login', [AuthController::class, 'login']);

// مسارات محمية بـ Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/visit/check-in', [VisitController::class, 'checkIn']);
    Route::post('/visit/check-out', [VisitController::class, 'checkOut']);
});
