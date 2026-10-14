<?php
/**
 * UMS EduGate — configuration example (Phase 1)
 *
 * Copy this file to config/config.local.php and fill in real values.
 * config.local.php is git-ignored and must NEVER be committed.
 * The web root is public/, so this file is not directly browser-accessible
 * when Apache's DocumentRoot points at public/.
 */

return [
    'app' => [
        'name'         => 'UMS EduGate',
        'env'          => 'local',            // local | production
        'debug'        => true,               // never enable in production
        'base_url'     => 'http://localhost:8000',
        'timezone'     => 'UTC',
    ],

    'database' => [
        'driver'   => 'mysql',                // mysql (PDO)
        'host'     => '127.0.0.1',            // XAMPP default: localhost
        'port'     => 3306,
        'name'     => 'ums_edugate',          // created in Phase 2
        'user'     => 'root',
        'pass'     => '',                     // put your real password ONLY in config.local.php
        'charset'  => 'utf8mb4',
        'options'  => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ],
    ],

    'verification' => [
        'stale_days' => 180,                  // PRD default staleness threshold
    ],
];
