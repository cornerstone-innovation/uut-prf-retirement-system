<?php

return [

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    'smile_id' => [
        'enabled' => env('SMILE_ID_ENABLED', false),
        'mode' => env('SMILE_ID_MODE', 'mock'),
        'api_key' => env('SMILE_ID_API_KEY'),
        'partner_id' => env('SMILE_ID_PARTNER_ID'),
        'callback_url' => env('SMILE_ID_CALLBACK_URL'),
        'base_url' => env('SMILE_ID_BASE_URL'),
    ],

    'clickpesa' => [
        'enabled' => env('CLICKPESA_ENABLED', false),
        'mode' => env('CLICKPESA_MODE', 'mock'), // mock | live

        // Base & endpoint URLs
        'base_url' => env('CLICKPESA_BASE_URL'),
        'auth_url' => env('CLICKPESA_AUTH_URL'),
        'checkout_link_url' => env('CLICKPESA_CHECKOUT_LINK_URL'),

        // Credentials
        'client_id' => env('CLICKPESA_CLIENT_ID'),
        'api_key' => env('CLICKPESA_API_KEY'),
        'api_secret' => env('CLICKPESA_API_SECRET'),
        'webhook_secret' => env('CLICKPESA_WEBHOOK_SECRET'),

        // URLs
        'return_url' => env('CLICKPESA_RETURN_URL'),
        'webhook_url' => env('CLICKPESA_WEBHOOK_URL'),

        // Optional
        'currency' => env('CLICKPESA_CURRENCY', 'TZS'),
        'integration_type' => env('CLICKPESA_INTEGRATION_TYPE', 'hosted'),
        'timeout_seconds' => env('CLICKPESA_TIMEOUT_SECONDS', 30),
    ],

];