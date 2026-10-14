<?php
/**
 * UMS EduGate — front controller (public/index.php)
 *
 * All requests are routed through this file (Apache via .htaccess,
 * PHP built-in server via router). Phase 1 exposes only the foundation
 * status page, a health endpoint, and a deliberate 404 for everything else.
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0'); // errors go to logs, never to the browser

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/Views/layouts/main.php'; // view renderer + e()
require_once BASE_PATH . '/app/Controllers/HomeController.php';
require_once BASE_PATH . '/app/Controllers/AuthController.php';
require_once BASE_PATH . '/app/Controllers/CountryController.php';   // Phase 5
require_once BASE_PATH . '/app/Controllers/UniversityController.php';// Phase 5
require_once BASE_PATH . '/app/Controllers/CampusController.php';    // Phase 6
require_once BASE_PATH . '/app/Controllers/IntakeController.php';    // Phase 6
require_once BASE_PATH . '/app/Controllers/RoundController.php';     // Phase 6
require_once BASE_PATH . '/app/Controllers/ProgramController.php';   // Phase 7

$config = Database::config();
date_default_timezone_set($config['app']['timezone'] ?? 'UTC');

if (!empty($config['app']['debug'])) {
    ini_set('display_errors', '1'); // local development only
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rtrim($path, '/') ?: '/';

// Serve existing static assets under public/ directly (built-in server / fallbacks).
if ($path !== '/' && is_file(BASE_PATH . '/public' . $path)) {
    return false;
}

$routes = require BASE_PATH . '/routes/web.php';

$handler = $routes[$method][$path] ?? null;

// Phase 5/6/7: parameterised module routes — /{module}/{id}[/{action}]
if ($handler === null && preg_match('#^/(countries|universities|campuses|intakes|rounds|programs)/(\d+)(?:/([a-z]+))?$#', $path, $m)) {
    $class = match ($m[1]) {
        'countries'    => 'CountryController',
        'universities' => 'UniversityController',
        'campuses'     => 'CampusController',
        'intakes'      => 'IntakeController',
        'programs'     => 'ProgramController',
        default        => 'RoundController',
    };
    $map = [
        ['GET',  '',       'show'],
        ['GET',  'edit',   'edit'],
        ['POST', 'update', 'update'],
        ['POST', 'archive','archive'],
        ['POST', 'restore','restore'],
    ];
    foreach ($map as [$rm, $ra, $act]) {
        if ($method === $rm && $ra === ($m[3] ?? '')) {
            $handler = [$class, $act];
            break;
        }
    }
}

// PRD: unsupported routes must return a deliberate 404/403, never a blank page.
if ($handler === null) {
    HomeController::render404();
    exit;
}

try {
    [$class, $action] = $handler;
    $controller = new $class();
    $controller->{$action}();
} catch (Throwable $e) {
    error_log('[UMS EduGate] Uncaught: ' . $e->getMessage());
    http_response_code(500);
    if (!empty($config['app']['debug'])) {
        echo '<pre style="padding:1rem">' . e($e->getMessage()) . '</pre>';
    } else {
        echo 'Internal server error.';
    }
}
