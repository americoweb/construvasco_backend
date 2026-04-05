<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Order\Contracts\OrderRepositoryInterface;
use App\Repositories\Order\Contracts\OrderItemRepositoryInterface;
use App\Repositories\Order\Eloquent\EloquentOrderRepository;
use App\Repositories\Order\Eloquent\EloquentOrderItemRepository;

class OrderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            OrderRepositoryInterface::class,
            EloquentOrderRepository::class
        );

        $this->app->bind(
            OrderItemRepositoryInterface::class,
            EloquentOrderItemRepository::class
        );
    }

    public function boot(): void
    {
        //
    }
}
