<?php

// TEMP-DEBUG: log Authorization header for charging-sessions requests
if (str_contains($_SERVER['REQUEST_URI'] ?? '', '/charging-sessions')) {
    @file_put_contents(
        '/tmp/auth_debug.log',
        date('H:i:s.u')." {$_SERVER['REQUEST_METHOD']} {$_SERVER['REQUEST_URI']}\n".
        "  Authorization: ".($_SERVER['HTTP_AUTHORIZATION'] ?? 'NONE')."\n".
        "  Origin: ".($_SERVER['HTTP_ORIGIN'] ?? 'none')."\n".
        "  Content-Type: ".($_SERVER['HTTP_CONTENT_TYPE'] ?? 'none')."\n\n",
        FILE_APPEND
    );
}

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
