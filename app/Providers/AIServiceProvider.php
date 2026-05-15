<?php

namespace App\Providers;

use App\Services\AI\ArchitecturalAiService;
use App\Services\AI\Contracts\AIServiceInterface;
use App\Services\AI\GeminiService;
use Illuminate\Support\ServiceProvider;

class AIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GeminiService::class);
        $this->app->singleton(ArchitecturalAiService::class);
        $this->app->bind(AIServiceInterface::class, GeminiService::class);
    }

    public function boot(): void
    {
        //
    }
}
