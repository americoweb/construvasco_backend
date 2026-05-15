<?php

use App\Http\Controllers\Admin\AdminClientController;
use App\Http\Controllers\Admin\AdminCreditPackageController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminProjectTemplateController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\AdminStaffController;
use App\Http\Controllers\Manager\ManagerProjectController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:api', 'role:admin,api'])->group(function () {
    Route::get('admin/dashboard', [AdminDashboardController::class, 'index']);

    Route::get('admin/clients/search', [AdminClientController::class, 'search']);
    Route::post('admin/clients', [AdminClientController::class, 'store']);

    Route::get('admin/staff/designers', [AdminStaffController::class, 'designers']);
    Route::apiResource('admin/staff', AdminStaffController::class);

    Route::get('admin/projects', [ManagerProjectController::class, 'index']);
    Route::patch('admin/projects/{id}/assign', [ManagerProjectController::class, 'assign']);

    Route::get('admin/project-templates', [AdminProjectTemplateController::class, 'index']);
    Route::post('admin/project-templates', [AdminProjectTemplateController::class, 'store']);
    Route::put('admin/project-templates/{id}', [AdminProjectTemplateController::class, 'update']);
    Route::delete('admin/project-templates/{id}', [AdminProjectTemplateController::class, 'destroy']);
    Route::post('admin/project-templates/{id}/phases', [AdminProjectTemplateController::class, 'storePhase']);
    Route::delete('admin/project-templates/{id}/phases/{phaseId}', [AdminProjectTemplateController::class, 'destroyPhase']);

    Route::get('admin/credit-packages', [AdminCreditPackageController::class, 'index']);
    Route::post('admin/credit-packages', [AdminCreditPackageController::class, 'store']);
    Route::put('admin/credit-packages/{id}', [AdminCreditPackageController::class, 'update']);
    Route::delete('admin/credit-packages/{id}', [AdminCreditPackageController::class, 'destroy']);

    Route::get('admin/reports/financial', [AdminReportController::class, 'financial']);
    Route::get('admin/reports/operational', [AdminReportController::class, 'operational']);
});
