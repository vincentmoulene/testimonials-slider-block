<?php

/*
 * Development router for PHP's built-in server:
 *
 *     php -S 127.0.0.1:8000 -t public router.php
 *
 * Without it the front controller answers every request, including the compiled
 * CSS and JavaScript under public/assets — so the site renders unstyled.
 * Returning false hands an existing file back to the server untouched.
 */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', \PHP_URL_PATH);

if (\is_string($path) && '/' !== $path && is_file(__DIR__.'/public'.$path)) {
    return false;
}

require __DIR__.'/public/index.php';
