<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Cart\CartController;

/*
|--------------------------------------------------------------------------
| Cart API Routes
|--------------------------------------------------------------------------
|
| Routes for Amazing Brindes Cart Module
|
*/

Route::prefix('v1')->group(function () {
    
    Route::prefix('cart')->group(function () {
        // Get or create cart
        Route::get('/', [CartController::class, 'show']);
        
        // Get cart by ID
        Route::get('/{id}', [CartController::class, 'showById'])->where('id', '[0-9]+');
        
        // Get cart by session
        Route::get('/session/{sessionId}', [CartController::class, 'showBySession']);
        
        // Add item to cart
        Route::post('/items', [CartController::class, 'addItem']);
        
        // Update item quantity
        Route::put('/items/{itemId}', [CartController::class, 'updateItem']);
        
        // Remove item from cart
        Route::delete('/items/{itemId}', [CartController::class, 'removeItem']);
        
        // Clear cart
        Route::delete('/{cartId}/clear', [CartController::class, 'clear']);
        
        // Get cart summary
        Route::get('/{cartId}/summary', [CartController::class, 'summary']);
        
        // Convert cart to order items
        Route::get('/{cartId}/checkout-items', [CartController::class, 'convertToOrder']);
        
        // Extend cart expiration
        Route::post('/{cartId}/extend', [CartController::class, 'extendExpiration']);
    });

    // Authenticated cart routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/cart/merge', [CartController::class, 'merge']);
    });
});
