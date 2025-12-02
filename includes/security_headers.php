<?php

if (!defined('SECURITY_HEADERS_LOADED')) {
    define('SECURITY_HEADERS_LOADED', true);

    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

    $csp = "default-src 'self'; " .
        "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
        "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
        "font-src 'self' https://cdnjs.cloudflare.com; " .
        "img-src 'self' data: https:; " .
        "connect-src 'self';";
    header("Content-Security-Policy: $csp");

    if (function_exists('header_remove')) {
        header_remove('X-Powered-By');
        header_remove('Server');
    }
}
