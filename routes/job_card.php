<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\JobCardController;
use App\Http\Controllers\Admin\JobCardFileController;
use App\Http\Controllers\Admin\JobCardDesignController;

/*
|--------------------------------------------------------------------------
| Job Card Routes (all require auth:api)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware('auth:api')->group(function () {

    // Core CRUD
    Route::get('admin/job-cards',                [JobCardController::class, 'index']);
    Route::post('admin/job-cards',               [JobCardController::class, 'store']);
    Route::get('admin/job-cards/kanban',         [JobCardController::class, 'kanban']);
    Route::get('admin/job-cards/{id}',           [JobCardController::class, 'show']);
    Route::put('admin/job-cards/{id}',           [JobCardController::class, 'update']);
    Route::delete('admin/job-cards/{id}',        [JobCardController::class, 'destroy']);

    // Status management
    Route::patch('admin/job-cards/{id}/status',         [JobCardController::class, 'updateStatus']);
    Route::post('admin/job-cards/{id}/cancel',          [JobCardController::class, 'cancel']);

    // Priority management
    Route::patch('admin/job-cards/{id}/priority',           [JobCardController::class, 'updatePriority']);
    Route::delete('admin/job-cards/{id}/priority-override', [JobCardController::class, 'removeOverride']);

    // Team assignment
    Route::post('admin/job-cards/{id}/assign-designer', [JobCardController::class, 'assignDesigner']);

    // Feedback / comments
    Route::post('admin/job-cards/{id}/feedback', [JobCardController::class, 'addFeedback']);

    // Link to Order
    Route::post('admin/job-cards/{id}/link-order', [JobCardController::class, 'linkOrder']);

    // File management
    Route::get('admin/job-cards/{id}/files',                   [JobCardFileController::class, 'index']);
    Route::post('admin/job-cards/{id}/files',                  [JobCardFileController::class, 'store']);
    Route::get('admin/job-cards/{id}/files/{fileId}/serve',    [JobCardFileController::class, 'serve']);
    Route::delete('admin/job-cards/{id}/files/{fileId}',       [JobCardFileController::class, 'destroy']);

    // Design workspace
    Route::post('admin/job-cards/{id}/design/generate',   [JobCardDesignController::class, 'generate']);
    Route::get('admin/job-cards/{id}/design/export-pdf',  [JobCardDesignController::class, 'exportPdf']);
});
