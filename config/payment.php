<?php

return [
    'base_url' => env('PAYMENT_BASE_URL', 'https://e2payments.explicador.co.mz'),
    'client_id' => env('PAYMENT_CLIENT_ID', ''),
    'client_secret' => env('PAYMENT_CLIENT_SECRET', ''),
    'test_mode' => (bool) env('PAYMENT_TEST_MODE', true),
    'webhook_secret' => env('PAYMENT_WEBHOOK_SECRET'),
];
