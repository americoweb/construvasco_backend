<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Checkout Settings
    |--------------------------------------------------------------------------
    */
    
    'currency' => env('CHECKOUT_CURRENCY', 'MT'),
    
    'free_shipping_threshold' => env('FREE_SHIPPING_THRESHOLD', 5000),
    
    'default_city' => env('CHECKOUT_DEFAULT_CITY', 'Maputo'),
    
    'default_country' => env('CHECKOUT_DEFAULT_COUNTRY', 'Moçambique'),
    
    /*
    |--------------------------------------------------------------------------
    | Shipping Rates
    |--------------------------------------------------------------------------
    */
    
    'shipping_rates' => [
        'maputo' => 150,
        'matola' => 250,
        'beira' => 500,
        'nampula' => 600,
        'default' => 350,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Admin Notifications
    |--------------------------------------------------------------------------
    */
    
    'admin_email' => env('CHECKOUT_ADMIN_EMAIL', 'mikemiranda.m2@gmail.com'),
    
    'admin_whatsapp' => env('CHECKOUT_ADMIN_WHATSAPP', '+258849999999'),
    
    /*
    |--------------------------------------------------------------------------
    | Tax Settings
    |--------------------------------------------------------------------------
    */
    
    'tax_enabled' => env('CHECKOUT_TAX_ENABLED', false),
    
    'tax_rate' => env('CHECKOUT_TAX_RATE', 0),
];
