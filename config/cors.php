<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],
    'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', env('FRONTEND_URL', 'http://localhost:3000')))))),

    'allowed_origins_patterns' => [
        '#^https?://.*\.test(:\d+)?$#',
        '#^https?://localhost(:\d+)?$#',
        '#^https?://127\.0\.0\.1(:\d+)?$#',
        '#^https?://.*\.mayaenora\.com$#',
        '#^https?://mayaenora\.com$#',
        '#^https?://.*\.elvoabd\.com$#',
        '#^https?://elvoabd\.com$#',
        '#^https?://.*\.signaturebd\.net$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],
    'exposed_headers' => ['Authorization'],

    'max_age' => 0,
    'max_age' => 86400,

    'supports_credentials' => false,
    'supports_credentials' => true,

];
