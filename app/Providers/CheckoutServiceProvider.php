<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Checkout\CheckoutService;

class CheckoutServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CheckoutService::class, function ($app) {
            return new CheckoutService(
                $app->make(\App\Services\Cart\CartService::class),
                $app->make(\App\Services\Order\OrderService::class)
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
