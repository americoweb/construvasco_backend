<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\Forms\FormTemplate;
use App\Models\Forms\FormInstance;
use App\Observers\Forms\FormTemplateObserver;
use App\Observers\Forms\FormInstanceObserver;
use App\Repositories\Candidate\Contracts\CandidateRepositoryInterface;
use App\Repositories\Candidate\CandidateRepository;
use App\Repositories\JobCard\Contracts\JobCardRepositoryInterface;
use App\Repositories\JobCard\Eloquent\EloquentJobCardRepository;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register services
        $this->app->singleton(\App\Services\TenantService::class);
        $this->app->singleton(\App\Services\ActivityLogService::class);
        
        // Register repositories
        $this->app->bind(CandidateRepositoryInterface::class, CandidateRepository::class);
        $this->app->bind(JobCardRepositoryInterface::class, EloquentJobCardRepository::class);
        
        // Register AI resume parser
        $this->app->bind(\App\Contracts\AI\ResumeParserInterface::class, \App\Services\AI\DefaultResumeParser::class);
        // Register AI matching engine
        $this->app->bind(\App\Contracts\AI\MatchingEngineInterface::class, \App\Services\AI\DefaultMatchingEngine::class);
    }

    public function boot(): void
    {
        // Set default string length for schema
         Schema::defaultStringLength(191);

        // Configure Scramble API documentation (only if installed)
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
        // Set tenant context in views
        view()->composer('*', function ($view) {
            if (auth('api')->check()) {
                $tenantId = session('tenant_id', cache()->get('tenant_id_' . auth('api')->id(), 1));
                session(['tenant_id' => $tenantId]);
            }
        });


        FormTemplate::observe(FormTemplateObserver::class);
    FormInstance::observe(FormInstanceObserver::class);

    }
}

