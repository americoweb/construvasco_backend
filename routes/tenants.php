<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TenantController;

/*
|--------------------------------------------------------------------------
| Tenant API Routes
|--------------------------------------------------------------------------
|
| Routes for tenant management (users, invitations, etc.)
|
*/

Route::prefix('tenants')->middleware('auth:api')->group(function () {
    // Tenant list and management
    Route::get('/', [TenantController::class, 'index']);
    Route::post('/', [TenantController::class, 'store']);
    Route::post('switch', [TenantController::class, 'switch']);
    
    // Tenant users management
    Route::get('users', [TenantController::class, 'users']);
    Route::post('users', [TenantController::class, 'addUser']);
    Route::put('users/{userId}/role', [TenantController::class, 'updateUserRole']);
    Route::delete('users/{userId}', [TenantController::class, 'removeUser']);
    
    // Roles map
    Route::get('roles', [TenantController::class, 'roles']);
    
    // Tenant invitations
    Route::get('invitations', [TenantController::class, 'invitations']);
    Route::delete('invitations/{invitationId}', [TenantController::class, 'cancelInvitation']);
    Route::post('invitations/{invitationId}/resend', [TenantController::class, 'resendInvitation']);
});

