<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;

/*
|--------------------------------------------------------------------------
| User API Routes
|--------------------------------------------------------------------------
|
| Routes for user profile management
|
*/

Route::prefix('user')->middleware('auth:api')->group(function () {
    // Profile routes
    Route::get('profile', [UserController::class, 'profile']);
    Route::put('profile', [UserController::class, 'updateProfile']);
    
    // Avatar routes
    Route::post('avatar', [UserController::class, 'uploadAvatar']);
    
    // Tenant management routes
    Route::get('tenants', [UserController::class, 'getTenants']);
    Route::post('switch-tenant', [UserController::class, 'switchTenant']);
    
    // Settings routes
    Route::get('settings', [UserController::class, 'getSettings']);
    Route::put('settings', [UserController::class, 'updateSettings']);
});

