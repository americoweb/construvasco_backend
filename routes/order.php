<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Order\OrderController;

/*
|--------------------------------------------------------------------------
| Order API Routes
|--------------------------------------------------------------------------
|
| Routes for Amazing Brindes Order Module
|
*/

Route::prefix('v1')->group(function () {
    
    Route::prefix('orders')->group(function () {
        // Create order
        Route::post('/', [OrderController::class, 'store']);
        
        // Get order by ID
        Route::get('/{id}', [OrderController::class, 'show'])->where('id', '[0-9]+');
        
        // Get order by order number
        Route::get('/number/{orderNumber}', [OrderController::class, 'showByOrderNumber']);
        
        // Get order by UUID
        Route::get('/uuid/{uuid}', [OrderController::class, 'showByUuid']);
        
        // Update order
        Route::put('/{id}', [OrderController::class, 'update']);
        
        // Update shipping address
        Route::patch('/{id}/shipping-address', [OrderController::class, 'updateShippingAddress']);
        
        // Cancel order
        Route::post('/{id}/cancel', [OrderController::class, 'cancel']);
        
        // Get orders by session
        Route::get('/session/{sessionId}', [OrderController::class, 'getBySession']);
    });

    // Admin order routes
    Route::prefix('admin/orders')->group(function () {
        // Create order (manager / back-office — requires authentication)
        Route::post('/', [OrderController::class, 'store'])->middleware('auth:api');

        // List all orders
        Route::get('/', [OrderController::class, 'index']);
        
        // Get pending orders (must be before /{id} route)
        Route::get('/pending', [OrderController::class, 'getPending']);
        
        // Get active orders (must be before /{id} route)
        Route::get('/active', [OrderController::class, 'getActive']);
        
        // Get order by ID
        Route::get('/{id}', [OrderController::class, 'show'])->where('id', '[0-9]+');
        
        // Update order status
        Route::patch('/{id}/status', [OrderController::class, 'updateStatus']);
        
        // Quick status updates
        Route::post('/{id}/confirm', [OrderController::class, 'confirm']);
        Route::post('/{id}/in-production', [OrderController::class, 'markInProduction']);
        Route::post('/{id}/ship', [OrderController::class, 'markShipped']);
        Route::post('/{id}/deliver', [OrderController::class, 'markDelivered']);
        Route::post('/{id}/paid', [OrderController::class, 'markAsPaid']);
    });

    // Authenticated user orders
    Route::middleware('auth:api')->group(function () {
        Route::get('/user/orders', [OrderController::class, 'getByUser']);
    });
});
