<?php
/**
 * UMS EduGate — routes (Phase 1)
 *
 * Minimal front-controller route table. Business routes are added
 * module-by-module in later phases; anything unmatched returns a
 * deliberate 404 page (never a blank page).
 */

return [
    'GET' => [
        '/'          => ['HomeController', 'index'],   // foundation status page
        '/health'    => ['HomeController', 'health'],  // PHP + DB connectivity check
        '/login'     => ['AuthController', 'showLogin'],   // Phase 3: auth
        '/dashboard' => ['AuthController', 'dashboard'],   // Phase 3: auth (guarded)
        '/logout'    => ['AuthController', 'logout'],      // Phase 3: auth
        '/404'         => ['HomeController', 'notFound'],
        '/components'  => ['HomeController', 'components'], // Phase 3 UI reference
    ],
    'POST' => [
        '/login' => ['AuthController', 'login'],  // Phase 3: credential check + CSRF
    ],
];
