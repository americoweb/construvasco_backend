<?php

use App\Http\Controllers\Manager\ManagerDashboardController;
use App\Http\Controllers\Manager\ManagerFinanceController;
use App\Http\Controllers\Manager\ManagerPaymentController;
use App\Http\Controllers\Manager\ManagerProjectController;
use App\Http\Controllers\Manager\ManagerProjectRequestController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/manager')->middleware(['auth:api', 'role:project_manager|admin,api'])->group(function () {
    Route::get('dashboard', ManagerDashboardController::class);
    Route::get('finances/overview', [ManagerFinanceController::class, 'overview']);
    Route::get('payments/pending', [ManagerPaymentController::class, 'pending']);
    Route::get('project-requests', [ManagerProjectRequestController::class, 'index']);
    Route::post('project-requests', [ManagerProjectRequestController::class, 'store']);
    Route::post('project-requests/{id}/documents', [ManagerProjectRequestController::class, 'uploadDocument']);
    Route::get('project-requests/{id}', [ManagerProjectRequestController::class, 'show']);
    Route::patch('project-requests/{id}', [ManagerProjectRequestController::class, 'update']);
    Route::post('project-requests/{id}/approve', [ManagerProjectRequestController::class, 'approve']);
    Route::post('project-requests/{id}/reject', [ManagerProjectRequestController::class, 'reject']);
    Route::post('project-requests/{id}/quotes', [ManagerProjectRequestController::class, 'storeQuote']);

    Route::get('assignable-users', [ManagerProjectController::class, 'assignableUsers']);
    Route::get('projects', [ManagerProjectController::class, 'index']);
    Route::get('projects/{id}', [ManagerProjectController::class, 'show']);
    Route::post('projects/{id}/assign', [ManagerProjectController::class, 'assign']);
    Route::get('projects/{id}/deliverables', [ManagerProjectController::class, 'listDeliverables']);
    Route::post('projects/{id}/deliverables/{deliverableId}/approve', [ManagerProjectController::class, 'approveDeliverable']);
    Route::post('projects/{id}/deliverables/{deliverableId}/reject', [ManagerProjectController::class, 'rejectDeliverable']);
    Route::get('projects/{id}/deliverables/{deliverableId}/download', [ManagerProjectController::class, 'downloadDeliverable']);
    Route::post('projects/{id}/mark-architecture-delivered', [ManagerProjectController::class, 'markArchitectureDelivered']);
    Route::post('projects/{id}/payments/{paymentId}/confirm', [ManagerPaymentController::class, 'confirm']);
    Route::post('projects/{id}/payments/{paymentId}/reject', [ManagerPaymentController::class, 'reject']);
    Route::get('projects/{id}/payments/{paymentId}/proof/download', [ManagerPaymentController::class, 'downloadProof']);
    Route::post('projects/{id}/phases', [ManagerProjectController::class, 'addPhase']);
    Route::post('projects/{id}/approve-deliverables', [ManagerProjectController::class, 'approveDeliverables']);
    Route::post('projects/{id}/reject-deliverables', [ManagerProjectController::class, 'rejectDeliverables']);
});
