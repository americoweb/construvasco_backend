<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Service Configuration 
    |--------------------------------------------------------------------------
    */
    
    'default' => env('AI_SERVICE', 'gemini'),
    
    /*
    |--------------------------------------------------------------------------
    | Google Gemini Configuration
    |--------------------------------------------------------------------------
    */
    
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-pro'),
        'vision_model' => env('GEMINI_VISION_MODEL', 'gemini-2.5-flash-image'), // Use gemini-2.5-flash-image for image generation (matches MVP)
        'timeout' => env('GEMINI_TIMEOUT', 60),
        'max_retries' => env('GEMINI_MAX_RETRIES', 3),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Suggestion Settings
    |--------------------------------------------------------------------------
    */
    
    'suggestions' => [
        'max_per_request' => env('AI_MAX_SUGGESTIONS', 5),
        'cache_ttl' => env('AI_CACHE_TTL', 3600), // 1 hour
        'include_bundles' => env('AI_INCLUDE_BUNDLES', true),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Mockup Generation Settings
    |--------------------------------------------------------------------------
    */
    
    'mockups' => [
        'storage_path' => env('AI_MOCKUP_PATH', 'public/mockups'),
        'max_size' => env('AI_MOCKUP_MAX_SIZE', 5242880), // 5MB
        'allowed_formats' => ['png', 'jpg', 'jpeg', 'webp'],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Fallback Settings
    |--------------------------------------------------------------------------
    */
    
    'fallback' => [
        'enabled' => env('AI_FALLBACK_ENABLED', true),
        'log_errors' => env('AI_LOG_FALLBACK_ERRORS', true),
    ],
];
