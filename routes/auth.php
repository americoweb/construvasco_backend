<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication API Routes
|--------------------------------------------------------------------------
|
| Routes for user authentication (login, register, logout, etc.)
| Use the FQCN below so this never accidentally binds to App\Http\Controllers\AuthController.
|
*/

Route::prefix('auth')->group(function () {
    // Public routes (no authentication required)
    Route::post('login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::post('register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
    Route::post('google', [\App\Http\Controllers\Api\AuthController::class, 'googleLogin']);
    Route::get('validate-invitation', [\App\Http\Controllers\Api\AuthController::class, 'validateInvitation']);
    Route::get('validate-company', [\App\Http\Controllers\Api\AuthController::class, 'validateCompanyInvitation']);
    
    // Protected routes (authentication required)
    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
        Route::post('refresh', [\App\Http\Controllers\Api\AuthController::class, 'refresh']);
        Route::get('me', [\App\Http\Controllers\Api\AuthController::class, 'me']);
        Route::post('change-password', [\App\Http\Controllers\Api\AuthController::class, 'changePassword']);
    });
});

