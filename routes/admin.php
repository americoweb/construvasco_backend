<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminClientController;
use App\Http\Controllers\Admin\AdminStaffController;

/*
|--------------------------------------------------------------------------
| Admin overview API (authenticated)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware('auth:api')->group(function () {
    Route::get('admin/dashboard', [AdminDashboardController::class, 'index']);

    // Client search + create
    Route::get('admin/clients/search', [AdminClientController::class, 'search']);
    Route::post('admin/clients', [AdminClientController::class, 'store']);

    // Staff management — designers endpoint MUST come before apiResource
    // to prevent Laravel from resolving 'designers' as a {staff} parameter.
    Route::get('admin/staff/designers', [AdminStaffController::class, 'designers']);
    Route::apiResource('admin/staff', AdminStaffController::class);
});
