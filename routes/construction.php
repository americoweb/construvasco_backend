<?php

use App\Http\Controllers\Construction\AdminProjectController;
use App\Http\Controllers\Construction\PortfolioProjectController;
use App\Http\Controllers\Construction\ProjectController;
use App\Http\Controllers\Construction\ProjectPaymentController;
use App\Http\Controllers\Construction\TechnicianProjectController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware(['auth:api', 'tenant'])->group(function () {
        Route::post('projects', [ProjectController::class, 'store']);
        Route::get('projects/my', [ProjectController::class, 'myProjects']);
        Route::post('projects/{id}/payments', [ProjectPaymentController::class, 'store']);

        Route::get('admin/projects', [AdminProjectController::class, 'index']);
        Route::patch('admin/projects/{id}/assign', [AdminProjectController::class, 'assign']);

        Route::patch('project-managers/projects/{id}/status', [TechnicianProjectController::class, 'updateStatus']);
        Route::post('project-managers/projects/{id}/deliverables', [TechnicianProjectController::class, 'addDeliverable']);
    });

    Route::get('portfolio/projects', [PortfolioProjectController::class, 'index']);
});

