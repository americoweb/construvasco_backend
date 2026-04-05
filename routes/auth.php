<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| Authentication API Routes
|--------------------------------------------------------------------------
|
| Routes for user authentication (login, register, logout, etc.)
|
*/

Route::prefix('auth')->group(function () {
    // Public routes (no authentication required)
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('google', [AuthController::class, 'googleLogin']);
    Route::get('validate-invitation', [AuthController::class, 'validateInvitation']);
    Route::get('validate-company', [AuthController::class, 'validateCompanyInvitation']);
    
    // Protected routes (authentication required)
    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

