<?php

use App\Http\Controllers\Technician\TechnicianDashboardController;
use App\Http\Controllers\Technician\TechnicianProjectController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/technician')->middleware(['auth:api', 'role:technician|admin,api'])->group(function () {
    Route::get('dashboard', TechnicianDashboardController::class);
    Route::get('projects', [TechnicianProjectController::class, 'index']);
    Route::get('projects/{id}', [TechnicianProjectController::class, 'show']);
    Route::get('projects/{id}/deliverables', [TechnicianProjectController::class, 'listDeliverables']);
    Route::post('projects/{id}/deliverables', [TechnicianProjectController::class, 'storeDeliverable']);
    Route::get('projects/{id}/deliverables/{deliverableId}/download', [TechnicianProjectController::class, 'downloadDeliverable']);
    Route::patch('projects/{id}/phases/{phaseId}', [TechnicianProjectController::class, 'updatePhase']);
    Route::post('projects/{id}/phases/{phaseId}/deliverables', [TechnicianProjectController::class, 'uploadDeliverable']);
    Route::delete('deliverables/{id}', [TechnicianProjectController::class, 'destroyDeliverable']);
    Route::post('projects/{id}/submit-for-review', [TechnicianProjectController::class, 'submitForReview']);
});
