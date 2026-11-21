<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ServiceRequestController;
use Illuminate\Support\Facades\Route;

// Health check - public endpoint
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'Service Request Tracker API is running',
    ]);
});

// Authentication routes
Route::prefix('auth')->group(function () {

    // Login: 5 attempts per minute per IP and email
    Route::post('/login', [
        AuthController::class,
        'login',
    ])->middleware('throttle:login');

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/me', [
            AuthController::class,
            'me',
        ])->middleware('throttle:api');

        Route::post('/logout', [
            AuthController::class,
            'logout',
        ])->middleware('throttle:api');

    });
});

// Service request routes
Route::middleware('auth:sanctum')->group(function () {

    // General API limit: 60 requests per minute per user
    Route::middleware('throttle:api')->group(function () {

        Route::get('/requests', [
            ServiceRequestController::class,
            'index',
        ]);

        Route::post('/requests', [
            ServiceRequestController::class,
            'store',
        ]);

        Route::get('/requests/{id}', [
            ServiceRequestController::class,
            'show',
        ])->whereNumber('id');

    });

    // Status update: 20 requests per minute per user
    Route::patch('/requests/{id}/status', [
        ServiceRequestController::class,
        'updateStatus',
    ])
        ->whereNumber('id')
        ->middleware('throttle:service-request-update');

    // History: 60 requests per minute per user
    Route::get('/requests/{id}/history', [
        ServiceRequestController::class,
        'history',
    ])
        ->whereNumber('id')
        ->middleware('throttle:service-request-history');

});
