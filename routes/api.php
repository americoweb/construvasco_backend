<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Construvasco Digital
|--------------------------------------------------------------------------
*/

Route::get('test', function () {
    return response()->json(['message' => 'API is working']);
});

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/user.php';
require_once __DIR__ . '/tenants.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/payment.php';
require_once __DIR__ . '/ai.php';
require_once __DIR__ . '/admin.php';
require_once __DIR__ . '/portfolio.php';
require_once __DIR__ . '/customer.php';
require_once __DIR__ . '/manager.php';
require_once __DIR__ . '/technician.php';
require_once __DIR__ . '/notifications.php';
