<?php

/**
 * BUI Beta Set Alumni Election Voting System (VCAST)
 * Front Controller & Routing Engine
 * Hostinger Business Web Hosting Compatible
 */

declare(strict_types=1);

// Load Composer Autoloader
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} else {
    // Custom Autoloader fallback for PSR-4 App\ namespace
    spl_autoload_register(function ($class) {
        $prefix = 'App\\';
        $baseDir = __DIR__ . '/../app/';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    });
}

// Load .env variables if vlucas/phpdotenv exists or custom simple parser
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2) + [1 => null];
        if ($name !== null) {
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            $_ENV[$name] = $value;
            putenv("{$name}={$value}");
        }
    }
}

use App\Helpers\CSRFMiddleware;

// Simple Router
$routes = require __DIR__ . '/../routes/web.php';

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Handle dynamic parameterized routes (e.g. /admin/candidates/edit/{id})
$matchedHandler = null;
$params = [];

if (isset($routes[$requestMethod][$requestUri])) {
    $matchedHandler = $routes[$requestMethod][$requestUri];
} else {
    // Check regex matches
    if ($requestMethod === 'GET' && preg_match('#^/admin/candidates/edit/(\d+)$#', $requestUri, $matches)) {
        $matchedHandler = [\App\Controllers\CandidateController::class, 'edit'];
        $params = [(int)$matches[1]];
    } elseif ($requestMethod === 'POST' && preg_match('#^/admin/candidates/update/(\d+)$#', $requestUri, $matches)) {
        $matchedHandler = [\App\Controllers\CandidateController::class, 'update'];
        $params = [(int)$matches[1]];
    } elseif ($requestMethod === 'POST' && preg_match('#^/admin/candidates/toggle/(\d+)$#', $requestUri, $matches)) {
        $matchedHandler = [\App\Controllers\CandidateController::class, 'toggleStatus'];
        $params = [(int)$matches[1]];
    } elseif ($requestMethod === 'POST' && preg_match('#^/admin/candidates/delete/(\d+)$#', $requestUri, $matches)) {
        $matchedHandler = [\App\Controllers\CandidateController::class, 'delete'];
        $params = [(int)$matches[1]];
    }
}

if ($matchedHandler) {
    list($controllerClass, $method) = $matchedHandler;
    
    // Check CSRF for POST requests
    if ($requestMethod === 'POST') {
        \App\Middleware\CSRFMiddleware::handle();
    }
    
    $controller = new $controllerClass();
    call_user_func_array([$controller, $method], $params);
} else {
    http_response_code(404);
    echo "<h1>404 Page Not Found</h1><p>The requested page '{$requestUri}' does not exist on VCAST.</p>";
}
