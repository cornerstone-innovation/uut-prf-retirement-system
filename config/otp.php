<?php

return [

    'driver' => env('OTP_DRIVER', 'mock'),

    'expiry_minutes' => (int) env('OTP_EXPIRY_MINUTES', 10),
    'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),

    'beem' => [
        'base_url' => env('BEEM_OTP_BASE_URL', 'https://apiotp.beem.africa/v1'),
        'api_key' => env('BEEM_OTP_API_KEY'),
        'secret_key' => env('BEEM_OTP_SECRET_KEY'),
        'app_id' => env('BEEM_OTP_APP_ID'),
        'timeout' => (int) env('BEEM_OTP_TIMEOUT', 30),
    ],

];