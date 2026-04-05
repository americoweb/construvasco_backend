<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Amazing Print-on-Demand MVP
|--------------------------------------------------------------------------
|
| Routes for the AI Print-on-Demand MVP Platform
| Based on PRD requirements
|
*/

// Health check / Test route
Route::get('test', function () {
    return response()->json(['message' => 'API is working']);
});

// Include Amazing app route modules
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/user.php';
require_once __DIR__ . '/tenants.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/product.php';
require_once __DIR__ . '/design.php';
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/checkout.php';
require_once __DIR__ . '/order.php';
require_once __DIR__ . '/payment.php';
require_once __DIR__ . '/ai.php';

// Optional: Include candidate routes if needed (for future features)
// require_once __DIR__ . '/candidate.php';
