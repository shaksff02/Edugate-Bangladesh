<?php
/**
 * UMS EduGate — routes
 *
 * Front-controller route table. Business routes are added module-by-module
 * in later phases; anything unmatched returns a deliberate 404 page
 * (never a blank page).
 *
 * NOTE: the front controller resolves handlers by exact "METHOD path" key.
 * Parameterised pages ({id}, {id}/action) are listed explicitly so unknown
 * ids still reach a controller that renders a proper 404 view.
 */

$crud = static function (string $module, string $class): array {
    return [
        '/' . $module              => [$class, 'index'],
        '/' . $module . '/create'  => [$class, 'create'],
        '/' . $module . '/store'   => [$class, 'store'],
    ];
};

$getRoutes = static function () use ($crud): array {
    $routes = [
        '/'          => ['HomeController', 'index'],    // foundation status page
        '/health'    => ['HomeController', 'health'],   // PHP + DB connectivity check
        '/login'     => ['AuthController', 'showLogin'],
        '/dashboard' => ['AuthController', 'dashboard'],
        '/logout'    => ['AuthController', 'logout'],
        '/404'       => ['HomeController', 'notFound'],
        '/components'=> ['HomeController', 'components'], // UI reference (Phase 3)
    ];
    foreach ($crud('countries', 'CountryController') as $k => $v) { $routes[$k] = $v; }
    foreach ($crud('universities', 'UniversityController') as $k => $v) { $routes[$k] = $v; }
    // Phase 6: campuses, intakes & application rounds
    foreach ($crud('campuses', 'CampusController') as $k => $v) { $routes[$k] = $v; }
    foreach ($crud('intakes', 'IntakeController') as $k => $v) { $routes[$k] = $v; }
    foreach ($crud('rounds', 'RoundController') as $k => $v) { $routes[$k] = $v; }
    $routes['/rounds/deadlines'] = ['RoundController', 'deadlines'];
    return $routes;
};

$postRoutes = static function (): array {
    $routes = ['/login' => ['AuthController', 'login']];
    return $routes;
};

return [
    'GET'  => $getRoutes(),
    'POST' => $postRoutes(),
];
