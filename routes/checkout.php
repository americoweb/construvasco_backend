<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Checkout\CheckoutController;

Route::prefix('v1/checkout')->group(function () {
    // Get checkout summary for a cart
    Route::get('summary/{cartUuid}', [CheckoutController::class, 'getSummary']);
    
    // Get shipping options
    Route::get('shipping/{cartUuid}', [CheckoutController::class, 'getShippingOptions']);
    
    // Validate checkout data before submitting
    Route::post('validate', [CheckoutController::class, 'validateData']);
    
    // Process checkout - create order from cart
    // Note: Route doesn't require auth, but will use authenticated user if token is present
    Route::post('process', [CheckoutController::class, 'process']);
});
