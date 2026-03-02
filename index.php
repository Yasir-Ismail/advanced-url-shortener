<?php
/**
 * Front controller / router.
 *
 * Routes:
 *   /                → create link page
 *   /dashboard       → analytics dashboard
 *   /admin/links     → link management
 *   /admin/analytics → per-link detail
 *   /{code}          → redirect
 */

// Start session for CSRF
session_start();

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Strip the base path (e.g. /url-shortener/) to get the route
$basePath = parse_url('http://localhost/url-shortener/', PHP_URL_PATH); // matches config
$route = '/' . trim(substr($requestUri, strlen(rtrim($basePath, '/'))), '/');

switch (true) {
    case $route === '/' || $route === '':
        require __DIR__ . '/public/index.php';
        break;

    case $route === '/dashboard':
        require __DIR__ . '/public/dashboard.php';
        break;

    case $route === '/admin/links':
        require __DIR__ . '/admin/links.php';
        break;

    case $route === '/admin/analytics':
        require __DIR__ . '/admin/analytics.php';
        break;

    case $route === '/api/create':
        require __DIR__ . '/api/create.php';
        break;

    case $route === '/api/toggle':
        require __DIR__ . '/api/toggle.php';
        break;

    case $route === '/api/delete':
        require __DIR__ . '/api/delete.php';
        break;

    default:
        // Treat remaining path segment as short code
        $code = trim($route, '/');
        if (preg_match('/^[a-zA-Z0-9]{1,16}$/', $code)) {
            $_GET['c'] = $code;
            require __DIR__ . '/public/redirect.php';
        } else {
            http_response_code(404);
            require __DIR__ . '/templates/404.php';
        }
        break;
}
