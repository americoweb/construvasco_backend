<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PermissionController;

/*
|--------------------------------------------------------------------------
| Permissions API Routes
|--------------------------------------------------------------------------
|
| Routes for permission and role management
|
*/

Route::prefix('permissions')->middleware('auth:api')->group(function () {
    // Permission matrix
    Route::get('matrix', [PermissionController::class, 'matrix']);
    
    // Permissions
    Route::get('/', [PermissionController::class, 'index']);
    
    // Roles
    Route::get('roles', [PermissionController::class, 'roles']);
    Route::get('roles/{role}/permissions', [PermissionController::class, 'rolePermissions']);
    Route::put('roles/{role}/permissions', [PermissionController::class, 'updateRolePermissions']);
    
    // User permissions
    Route::get('user', [PermissionController::class, 'userPermissions']);
    Route::put('user', [PermissionController::class, 'updateUserPermissions']);
});

