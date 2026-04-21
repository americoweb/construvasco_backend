<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Models\Forms\FormTemplate;
use App\Models\Forms\FormInstance;
use App\Observers\Forms\FormTemplateObserver;
use App\Observers\Forms\FormInstanceObserver;
use App\Repositories\Candidate\Contracts\CandidateRepositoryInterface;
use App\Repositories\Candidate\CandidateRepository;
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
use App\Repositories\Design\Contracts\DesignRepositoryInterface;
use App\Repositories\Design\Contracts\DesignRefinementRepositoryInterface;
use App\Repositories\Design\Eloquent\EloquentDesignRepository;
use App\Repositories\Design\Eloquent\EloquentDesignRefinementRepository;
use App\Repositories\Cart\Contracts\CartRepositoryInterface;
use App\Repositories\Cart\Contracts\CartItemRepositoryInterface;
use App\Repositories\Cart\Eloquent\EloquentCartRepository;
use App\Repositories\Cart\Eloquent\EloquentCartItemRepository;
use App\Repositories\Order\Contracts\OrderRepositoryInterface;
use App\Repositories\Order\Contracts\OrderItemRepositoryInterface;
use App\Repositories\Order\Eloquent\EloquentOrderRepository;
use App\Repositories\Order\Eloquent\EloquentOrderItemRepository;
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
        
        // Register Product repositories
        $this->app->bind(ProductRepositoryInterface::class, EloquentProductRepository::class);
        $this->app->bind(ProductColorRepositoryInterface::class, EloquentProductColorRepository::class);
        $this->app->bind(ProductPrintAreaRepositoryInterface::class, EloquentProductPrintAreaRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, EloquentCategoryRepository::class);
        $this->app->bind(TagRepositoryInterface::class, EloquentTagRepository::class);
        
        // Register Design repositories
        $this->app->bind(DesignRepositoryInterface::class, EloquentDesignRepository::class);
        $this->app->bind(DesignRefinementRepositoryInterface::class, EloquentDesignRefinementRepository::class);
        
        // Register Cart repositories
        $this->app->bind(CartRepositoryInterface::class, EloquentCartRepository::class);
        $this->app->bind(CartItemRepositoryInterface::class, EloquentCartItemRepository::class);
        
        // Register Order repositories
        $this->app->bind(OrderRepositoryInterface::class, EloquentOrderRepository::class);
        $this->app->bind(OrderItemRepositoryInterface::class, EloquentOrderItemRepository::class);

        // Register JobCard repositories
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
