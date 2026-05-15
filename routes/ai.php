<?php

use App\Http\Controllers\AI\AiGenerationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/ai')->middleware(['auth:api', 'role:customer|admin,api'])->group(function () {
    Route::get('generations', [AiGenerationController::class, 'index']);
    Route::post('generations', [AiGenerationController::class, 'store']);
    Route::get('generations/{id}', [AiGenerationController::class, 'show'])->where('id', '[0-9]+');
    Route::post('generations/{id}/refine', [AiGenerationController::class, 'refine'])->where('id', '[0-9]+');
    Route::get('health', [AiGenerationController::class, 'healthCheck']);
});
