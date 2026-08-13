<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Forzar encabezados CORS globales para Hostinger CDN (hcdn)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN, Accept, Origin");
header("Access-Control-Max-Age: 86400");

// Interceptar peticiones OPTIONS (CORS preflight) incondicionalmente
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Normalizar REQUEST_URI si incluye prefijos de carpetas de Hostinger
if (isset($_SERVER['REQUEST_URI'])) {
    if (str_starts_with($_SERVER['REQUEST_URI'], '/public/api/')) {
        $_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], 7);
    } elseif (str_starts_with($_SERVER['REQUEST_URI'], '/ren_api/public/api/')) {
        $_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], 15);
    } elseif (str_starts_with($_SERVER['REQUEST_URI'], '/ren-api/public/api/')) {
        $_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], 15);
    }
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
