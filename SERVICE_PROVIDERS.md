# Service Provider Registration

Add these to your `config/app.php` providers array or create dedicated service providers:

## Repository Bindings
```php
// In AppServiceProvider boot() method or dedicated provider
$this->app->bind(
    \App\Repositories\Candidate\Contracts\CandidateRepositoryInterface::class,
    \App\Repositories\Candidate\CandidateRepository::class
);
```

## AI Service Bindings
```php
// AI service bindings with fallbacks
$this->app->bind(
    \App\Contracts\AI\ResumeParserInterface::class,
    function ($app) {
        // Check if AI service is available
        if (config('ai.enabled') && config('ai.openai.key')) {
            return new \App\Services\AI\ResumeParsingAI();
        }
        return new \App\Services\AI\Fallbacks\RuleBasedResumeParser();
    }
);
```

## Route Registration
Add to `routes/api.php`:
```php
Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/v1')->group(function () {
    require base_path('routes/candidate.php');
});
```

## Composer Autoload
After running this script, make sure to run:
```bash
composer dump-autoload
```
