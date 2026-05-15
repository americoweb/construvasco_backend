<?php

use App\Http\Controllers\Manager\ManagerDashboardController;
use App\Http\Controllers\Manager\ManagerProjectController;
use App\Http\Controllers\Manager\ManagerProjectRequestController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/manager')->middleware(['auth:api', 'role:project_manager|admin,api'])->group(function () {
    Route::get('dashboard', ManagerDashboardController::class);
    Route::get('project-requests', [ManagerProjectRequestController::class, 'index']);
    Route::get('project-requests/{id}', [ManagerProjectRequestController::class, 'show']);
    Route::patch('project-requests/{id}', [ManagerProjectRequestController::class, 'update']);
    Route::post('project-requests/{id}/approve', [ManagerProjectRequestController::class, 'approve']);
    Route::post('project-requests/{id}/reject', [ManagerProjectRequestController::class, 'reject']);
    Route::post('project-requests/{id}/quotes', [ManagerProjectRequestController::class, 'storeQuote']);

    Route::get('projects', [ManagerProjectController::class, 'index']);
    Route::get('projects/{id}', [ManagerProjectController::class, 'show']);
    Route::post('projects/{id}/assign', [ManagerProjectController::class, 'assign']);
    Route::post('projects/{id}/phases', [ManagerProjectController::class, 'addPhase']);
    Route::post('projects/{id}/approve-deliverables', [ManagerProjectController::class, 'approveDeliverables']);
    Route::post('projects/{id}/reject-deliverables', [ManagerProjectController::class, 'rejectDeliverables']);
});
