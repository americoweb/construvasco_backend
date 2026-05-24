<?php

use App\Http\Controllers\Customer\CustomerCreditController;
use App\Http\Controllers\Customer\CustomerDashboardController;
use App\Http\Controllers\Customer\CustomerProjectController;
use App\Http\Controllers\Customer\CustomerProjectRequestController;
use App\Http\Controllers\Customer\CustomerQuoteController;
use App\Http\Controllers\Customer\CustomerStudioController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/customer')->middleware(['auth:api', 'role:customer,api'])->group(function () {
    Route::get('dashboard', CustomerDashboardController::class);

    Route::get('studio/state', [CustomerStudioController::class, 'state']);
    Route::post('studio/reset', [CustomerStudioController::class, 'reset']);
    Route::get('credits/balance', [CustomerCreditController::class, 'balance']);
    Route::get('credits/history', [CustomerCreditController::class, 'history']);
    Route::get('credit-packages', [CustomerCreditController::class, 'packages']);
    Route::post('credits/purchase/{packageId}', [CustomerCreditController::class, 'purchase']);

    Route::get('project-requests', [CustomerProjectRequestController::class, 'index']);
    Route::post('project-requests', [CustomerProjectRequestController::class, 'store']);
    Route::get('project-requests/{id}', [CustomerProjectRequestController::class, 'show']);
    Route::patch('project-requests/{id}', [CustomerProjectRequestController::class, 'update']);
    Route::post('project-requests/{id}/submit', [CustomerProjectRequestController::class, 'submit']);
    Route::post('project-requests/{id}/approve-ai-generation/{generationId}', [CustomerProjectRequestController::class, 'approveAiGeneration']);
    Route::get('project-requests/{id}/documents', [CustomerProjectRequestController::class, 'documents']);
    Route::post('project-requests/{id}/documents', [CustomerProjectRequestController::class, 'uploadDocument']);
    Route::delete('project-requests/documents/{documentId}', [CustomerProjectRequestController::class, 'destroyDocument']);

    Route::get('quotes/{id}', [CustomerQuoteController::class, 'show']);
    Route::post('quotes/{id}/accept', [CustomerQuoteController::class, 'accept']);
    Route::post('quotes/{id}/reject', [CustomerQuoteController::class, 'reject']);

    Route::get('projects', [CustomerProjectController::class, 'index']);
    Route::get('projects/{id}', [CustomerProjectController::class, 'show']);
    Route::post('projects/{id}/pay-final', [CustomerProjectController::class, 'payFinal']);
    Route::get('projects/{id}/deliverables', [CustomerProjectController::class, 'deliverables']);
    Route::get('projects/{id}/deliverables/{deliverableId}/download', [CustomerProjectController::class, 'downloadDeliverable']);
    Route::post('projects/{id}/payments/{paymentId}/proof', [CustomerProjectController::class, 'uploadPaymentProof']);
});
