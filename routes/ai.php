<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AI\SuggestionController;

Route::prefix('v1/ai')->group(function () {
    // Get smart product suggestions
    Route::post('suggestions', [SuggestionController::class, 'getSuggestions']);
    
    // Generate mockup for a specific product
    Route::post('mockup', [SuggestionController::class, 'generateMockup']);
    
    // Refine design based on feedback
    Route::post('refine', [SuggestionController::class, 'refineDesign']);
    
    // Health check
    Route::get('health', [SuggestionController::class, 'healthCheck']);
});
