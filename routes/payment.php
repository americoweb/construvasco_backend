<?php

use App\Http\Controllers\OnlinePayment\OnlinePaymentController;
use App\Http\Controllers\Construction\ProjectPaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Payment Routes
|--------------------------------------------------------------------------
|
| Routes for online payment processing (M-Pesa, Emola, Proof Upload)
| These routes are public and don't require authentication
|
*/

Route::group(['prefix' => 'payments'], function ($router) {
    Route::post('mpesa', [OnlinePaymentController::class, 'payWithMpesa']);
    Route::post('emola', [OnlinePaymentController::class, 'payWithEmola']);
    Route::post('proof-upload', [OnlinePaymentController::class, 'payWithProofUpload']);
    
    // Webhook endpoint for payment gateway callbacks
    // This should be publicly accessible (no auth required) but should validate webhook signature
    Route::post('webhook', [ProjectPaymentController::class, 'webhook']);
});

