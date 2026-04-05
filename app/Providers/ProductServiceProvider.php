<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Product\Contracts\ProductRepositoryInterface;
use App\Repositories\Product\Contracts\ProductColorRepositoryInterface;
use App\Repositories\Product\Contracts\ProductPrintAreaRepositoryInterface;
use App\Repositories\Product\Contracts\CategoryRepositoryInterface;
use App\Repositories\Product\Contracts\TagRepositoryInterface;
use App\Repositories\Product\Eloquent\EloquentProductRepository;
use App\Repositories\Product\Eloquent\EloquentProductColorRepository;
use App\Repositories\Product\Eloquent\EloquentProductPrintAreaRepository;
use App\Repositories\Product\Eloquent\EloquentCategoryRepository;
use App\Repositories\Product\Eloquent\EloquentTagRepository;

class ProductServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ProductRepositoryInterface::class,
            EloquentProductRepository::class
        );

        $this->app->bind(
            ProductColorRepositoryInterface::class,
            EloquentProductColorRepository::class
        );

        $this->app->bind(
            ProductPrintAreaRepositoryInterface::class,
            EloquentProductPrintAreaRepository::class
        );

        $this->app->bind(
            CategoryRepositoryInterface::class,
            EloquentCategoryRepository::class
        );

        $this->app->bind(
            TagRepositoryInterface::class,
            EloquentTagRepository::class
        );
    }

    public function boot(): void
    {
        //
    }
}
