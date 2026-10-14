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
        '/404'       => ['HomeController', 'notFound'],
    ],
    'POST' => [
        // Intentionally empty in Phase 1.
    ],
];
