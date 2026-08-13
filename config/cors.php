<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => explode(',', env(
        'CORS_ALLOWED_ORIGINS',
        'https://ren-admin.faceaut.com,https://faceaut.com,http://localhost:4200,http://localhost:8100'
    )),

    'allowed_origins_patterns' => [
        '#^https://.*\.faceaut\.com$#',
        '#^capacitor://.*$#',
        '#^ionic://.*$#',
        '#^http://localhost.*$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
    ],

    'max_age' => 86400,

    'supports_credentials' => true,

];
