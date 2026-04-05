<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Design\DesignController;
use App\Http\Controllers\Design\DesignRefinementController;

/*
|--------------------------------------------------------------------------
| Design API Routes
|--------------------------------------------------------------------------
|
| Routes for Amazing Brindes Design Module
|
*/

Route::prefix('v1')->group(function () {
    
    Route::prefix('designs')->group(function () {
        // Create new design
        Route::post('/', [DesignController::class, 'store']);
        
        // Get design by ID
        Route::get('/{id}', [DesignController::class, 'show']);
        
        // Get design by UUID
        Route::get('/uuid/{uuid}', [DesignController::class, 'showByUuid']);
        
        // Update design
        Route::put('/{id}', [DesignController::class, 'update']);
        
        // Delete design
        Route::delete('/{id}', [DesignController::class, 'destroy']);
        
        // Get designs by session (for anonymous users)
        Route::get('/session/{sessionId}', [DesignController::class, 'getBySession']);
        
        // Upload logo
        Route::post('/{id}/logo', [DesignController::class, 'uploadLogo']);
        Route::delete('/{id}/logo', [DesignController::class, 'removeLogo']);
        Route::get('/{id}/logo/base64', [DesignController::class, 'getLogoBase64']);
        Route::get('/{id}/logo/download', [DesignController::class, 'downloadLogo']);
        
        // Upload reference image
        Route::post('/{id}/reference-image', [DesignController::class, 'uploadReferenceImage']);
        Route::delete('/{id}/reference-image', [DesignController::class, 'removeReferenceImage']);
        Route::get('/{id}/reference-image/base64', [DesignController::class, 'getReferenceImageBase64']);
        Route::get('/{id}/reference-image/download', [DesignController::class, 'downloadReferenceImage']);
        
        // Mockup management
        Route::post('/{id}/mockup', [DesignController::class, 'saveMockup']);
        Route::post('/{id}/mockup/generate', [DesignController::class, 'generateMockup']);
        Route::post('/{id}/generating', [DesignController::class, 'markAsGenerating']);
        Route::post('/{id}/failed', [DesignController::class, 'markAsFailed']);
        
        // Design files for printing
        Route::get('/{id}/files', [DesignController::class, 'getDesignFilesForPrinting']);
        
        // Refinements
        Route::get('/{designId}/refinements', [DesignRefinementController::class, 'index']);
        Route::post('/{designId}/refinements', [DesignRefinementController::class, 'store']);
        Route::get('/{designId}/refinements/latest', [DesignRefinementController::class, 'latest']);
        Route::post('/{designId}/refinements/{refinementId}/mockup', [DesignRefinementController::class, 'saveMockup']);
        Route::post('/{designId}/refinements/{refinementId}/failed', [DesignRefinementController::class, 'markAsFailed']);
    });

    // Authenticated user designs
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user/designs', [DesignController::class, 'getByUser']);
    });

    // Admin design routes (protected by auth middleware)
    Route::prefix('admin/designs')->middleware('auth:api')->group(function () {
        Route::get('/', [DesignController::class, 'index']);
        Route::get('/{id}', [DesignController::class, 'show']);
        Route::put('/{id}', [DesignController::class, 'update']);
        Route::delete('/{id}', [DesignController::class, 'destroy']);
        Route::get('/{id}/refinements', [DesignRefinementController::class, 'index']);
        Route::get('/{id}/files', [DesignController::class, 'getDesignFilesForPrinting']);
        Route::get('/{id}/logo/download', [DesignController::class, 'downloadLogo']);
        Route::get('/{id}/reference-image/download', [DesignController::class, 'downloadReferenceImage']);
    });
});
