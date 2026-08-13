<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Aquí se configura qué orígenes, métodos y headers están permitidos
    | para el API REST de Rentame.
    |
    | - Angular Admin: http://localhost:4200
    | - Ionic App (dev): http://localhost:8100
    | - Ionic App (Capacitor): capacitor://localhost, ionic://localhost
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => explode(',', env(
        'CORS_ALLOWED_ORIGINS',
        'http://localhost:4200,http://localhost:8100'
    )),

    'allowed_origins_patterns' => [
        // Capacitor iOS/Android
        '#^capacitor://.*$#',
        '#^ionic://.*$#',
    ],

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-XSRF-TOKEN',
    ],

    'exposed_headers' => [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
    ],

    'max_age' => 86400, // 24 horas — cachea el preflight

    'supports_credentials' => true,

];
