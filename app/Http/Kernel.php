<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    // ... other properties and methods

    protected $routeMiddleware = [
        // ... other middleware
        'tenant' => \App\Http\Middleware\TenantMiddleware::class,
    ];
}