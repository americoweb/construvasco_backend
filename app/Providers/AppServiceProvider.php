<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use App\Models\AI\AiGeneration;
use App\Models\Construction\ProjectPayment;
use App\Models\Construction\ProjectRequest;
use App\Models\Construction\Quote;
use App\Models\Project;
use App\Observers\ProjectPaymentObserver;
use App\Policies\AiGenerationPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\ProjectRequestPolicy;
use App\Policies\QuotePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\TenantService::class);
        $this->app->singleton(\App\Services\ActivityLogService::class);
    }

    public function boot(): void
    {
        ProjectPayment::observe(ProjectPaymentObserver::class);

        Gate::policy(ProjectRequest::class, ProjectRequestPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Quote::class, QuotePolicy::class);
        Gate::policy(AiGeneration::class, AiGenerationPolicy::class);
        Schema::defaultStringLength(191);

        if (class_exists(\Dedoc\Scramble\Scramble::class)) {
            \Dedoc\Scramble\Scramble::afterOpenApiGenerated(function (\Dedoc\Scramble\Support\Generator\OpenApi $openApi) {
                $openApi->secure(
                    \Dedoc\Scramble\Support\Generator\SecurityScheme::http('bearer', 'JWT')
                );
            });
        }

        RateLimiter::for('auth-login', function (\Illuminate\Http\Request $request) {
            $key = strtolower((string) $request->input('identifier', $request->input('email', '')));
            return Limit::perMinute(5)->by($request->ip() . '|' . sha1($key));
        });

        RateLimiter::for('auth-register', function (\Illuminate\Http\Request $request) {
            return Limit::perHour(10)->by($request->ip());
        });

        RateLimiter::for('auth-google', function (\Illuminate\Http\Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        view()->composer('*', function ($view) {
            if (auth('api')->check()) {
                $tenantId = session('tenant_id', cache()->get('tenant_id_' . auth('api')->id(), 1));
                session(['tenant_id' => $tenantId]);
            }
        });
    }
}
