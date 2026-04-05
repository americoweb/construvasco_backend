<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Design\Contracts\DesignRepositoryInterface;
use App\Repositories\Design\Contracts\DesignRefinementRepositoryInterface;
use App\Repositories\Design\Eloquent\EloquentDesignRepository;
use App\Repositories\Design\Eloquent\EloquentDesignRefinementRepository;

class DesignServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            DesignRepositoryInterface::class,
            EloquentDesignRepository::class
        );

        $this->app->bind(
            DesignRefinementRepositoryInterface::class,
            EloquentDesignRefinementRepository::class
        );
    }

    public function boot(): void
    {
        //
    }
}
