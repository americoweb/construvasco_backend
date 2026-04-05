<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Cart\Contracts\CartRepositoryInterface;
use App\Repositories\Cart\Contracts\CartItemRepositoryInterface;
use App\Repositories\Cart\Eloquent\EloquentCartRepository;
use App\Repositories\Cart\Eloquent\EloquentCartItemRepository;

class CartServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CartRepositoryInterface::class,
            EloquentCartRepository::class
        );

        $this->app->bind(
            CartItemRepositoryInterface::class,
            EloquentCartItemRepository::class
        );
    }

    public function boot(): void
    {
        //
    }
}
