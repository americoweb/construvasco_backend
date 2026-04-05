<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Product\ProductColorController;
use App\Http\Controllers\Product\ProductPrintAreaController;
use App\Http\Controllers\Product\ProductSizeController;
use App\Http\Controllers\Product\ProductPriceController;
use App\Http\Controllers\Product\CategoryController;
use App\Http\Controllers\Product\TagController;
use App\Http\Controllers\Product\TestimonialController;

/*
|--------------------------------------------------------------------------
| Product API Routes
|--------------------------------------------------------------------------
|
| Routes for Amazing Brindes Product Module
|
*/

Route::prefix('v1')->group(function () {
    
    // Public product routes
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/active', [ProductController::class, 'active']);
        Route::get('/featured', [ProductController::class, 'featured']);
        Route::get('/slug/{slug}', [ProductController::class, 'showBySlug']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::post('/{id}/calculate-price', [ProductController::class, 'calculatePrice']);
        
        // Product colors
        Route::get('/{productId}/colors', [ProductColorController::class, 'index']);
        
        // Product print areas
        Route::get('/{productId}/print-areas', [ProductPrintAreaController::class, 'index']);
        
        // Product sizes
        Route::get('/{productId}/sizes', [ProductSizeController::class, 'index']);
        Route::get('/{productId}/size-restrictions', [ProductSizeController::class, 'getRestrictions']);
        
        // Product price calculation
        Route::post('/{productId}/calculate-price', [ProductPriceController::class, 'calculatePrice']);
        
        // Product testimonials
        Route::get('/{productId}/testimonials', [TestimonialController::class, 'forProduct']);
    });

    // Public category routes
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/active', [CategoryController::class, 'active']);
        Route::get('/root', [CategoryController::class, 'root']);
        Route::get('/{id}', [CategoryController::class, 'show']);
    });

    // Public tag routes
    Route::prefix('tags')->group(function () {
        Route::get('/', [TagController::class, 'index']);
        Route::get('/all', [TagController::class, 'all']);
        Route::get('/{id}', [TagController::class, 'show']);
    });

    // Public testimonial routes
    Route::prefix('testimonials')->group(function () {
        Route::get('/', [TestimonialController::class, 'index']);
        Route::get('/{id}', [TestimonialController::class, 'show']);
    });

    // Admin product routes (protected by auth middleware)
    Route::prefix('admin/products')->middleware('auth:api')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::post('/', [ProductController::class, 'store']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
        Route::post('/{id}/toggle-featured', [ProductController::class, 'toggleFeatured']);
        
        // Product image uploads
        Route::post('/{id}/upload-image', [ProductController::class, 'uploadImage']);
        Route::post('/{id}/upload-base-image', [ProductController::class, 'uploadBaseImage']);
        
        // Product colors management
        Route::get('/{productId}/colors', [ProductColorController::class, 'index']);
        Route::post('/{productId}/colors', [ProductColorController::class, 'store']);
        Route::put('/{productId}/colors/{colorId}', [ProductColorController::class, 'update']);
        Route::delete('/{productId}/colors/{colorId}', [ProductColorController::class, 'destroy']);
        Route::patch('/{productId}/colors/{colorId}/stock', [ProductColorController::class, 'updateStock']);
        
        // Product print areas management
        Route::get('/{productId}/print-areas', [ProductPrintAreaController::class, 'index']);
        Route::post('/{productId}/print-areas', [ProductPrintAreaController::class, 'store']);
        Route::put('/{productId}/print-areas/{areaId}', [ProductPrintAreaController::class, 'update']);
        Route::delete('/{productId}/print-areas/{areaId}', [ProductPrintAreaController::class, 'destroy']);
        
        // Product sizes management
        Route::get('/{productId}/sizes', [ProductSizeController::class, 'index']);
        Route::post('/{productId}/sizes', [ProductSizeController::class, 'store']);
        Route::put('/{productId}/sizes/{sizeId}', [ProductSizeController::class, 'update']);
        Route::delete('/{productId}/sizes/{sizeId}', [ProductSizeController::class, 'destroy']);
        Route::get('/{productId}/size-restrictions', [ProductSizeController::class, 'getRestrictions']);
        Route::post('/{productId}/size-restrictions', [ProductSizeController::class, 'storeRestrictions']);
        Route::put('/{productId}/size-restrictions/{restrictionId}', [ProductSizeController::class, 'updateRestrictions']);
        
        // Product testimonials management
        Route::get('/{productId}/testimonials', [TestimonialController::class, 'forProduct']);
    });

    // Admin category routes (protected by auth middleware)
    Route::prefix('admin/categories')->middleware('auth:api')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/{id}', [CategoryController::class, 'show']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::put('/{id}', [CategoryController::class, 'update']);
        Route::delete('/{id}', [CategoryController::class, 'destroy']);
        Route::post('/{id}/upload-image', [CategoryController::class, 'uploadImage']);
    });

    // Admin tag routes (protected by auth middleware)
    Route::prefix('admin/tags')->middleware('auth:api')->group(function () {
        Route::get('/', [TagController::class, 'index']);
        Route::get('/{id}', [TagController::class, 'show']);
        Route::post('/', [TagController::class, 'store']);
        Route::put('/{id}', [TagController::class, 'update']);
        Route::delete('/{id}', [TagController::class, 'destroy']);
    });

    // Admin testimonial routes (protected by auth middleware)
    Route::prefix('admin/testimonials')->middleware('auth:api')->group(function () {
        Route::get('/', [TestimonialController::class, 'index']);
        Route::get('/{id}', [TestimonialController::class, 'show']);
        Route::post('/', [TestimonialController::class, 'store']);
        Route::put('/{id}', [TestimonialController::class, 'update']);
        Route::delete('/{id}', [TestimonialController::class, 'destroy']);
        Route::patch('/{id}/toggle-active', [TestimonialController::class, 'toggleActive']);
    });
});
