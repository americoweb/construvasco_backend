<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AI\GeminiService;
use App\Services\AI\SuggestionService;
use App\Services\AI\FallbackSuggestionService;
use App\Services\AI\Contracts\AIServiceInterface;
use App\Services\AI\Contracts\SuggestionServiceInterface;

class AIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GeminiService::class);
        
        $this->app->singleton(FallbackSuggestionService::class);
        
        $this->app->singleton(SuggestionService::class, function ($app) {
            return new SuggestionService(
                $app->make(GeminiService::class),
                $app->make(FallbackSuggestionService::class)
            );
        });

        $this->app->bind(AIServiceInterface::class, GeminiService::class);
        $this->app->bind(SuggestionServiceInterface::class, SuggestionService::class);
    }

    public function boot(): void
    {
        //
    }
}
