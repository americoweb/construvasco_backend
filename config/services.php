<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

    'google_drive' => [
        // OAuth2 client secrets file (downloaded from Google Cloud Console)
        // stored at: storage/app/google-oauth-client.json
        // Token auto-saved to: storage/app/google-token.json after running drive:authorize
        'folder_id' => env('GOOGLE_DRIVE_FOLDER_ID', null),
    ],

    'whatsapp' => [
        'url'           => env('WHATSAPP_API_URL', 'https://hdxqelinqivwgmggolhs.supabase.co/functions/v1/api-gateway'),
        'api_key'       => env('WHATSAPP_API_KEY', ''),
        'bearer_token'  => env('WHATSAPP_BEARER_TOKEN', ''),
        'instance_name' => env('WHATSAPP_INSTANCE_NAME', ''),
    ],

];
