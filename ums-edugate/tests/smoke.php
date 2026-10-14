<?php
/**
 * UMS EduGate — Phase 1 smoke test (CLI).
 * Run: php tests/smoke.php
 * Verifies: config loads, DB abstraction handles failure/success cleanly,
 * routes file is valid, view renderer + escaping helper work.
 */

declare(strict_types=1);

$fail = 0;
function check(string $name, bool $ok, string $detail = ''): void {
    global $fail;
    echo ($ok ? 'PASS' : 'FAIL') . "  $name" . ($detail !== '' ? "  ($detail)" : '') . PHP_EOL;
    if (!$ok) { $fail++; }
}

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/Views/layouts/main.php';

// 1. Config loads and has required keys.
$config = Database::config();
check('config loads', isset($config['app']['name'], $config['database']['host'], $config['database']['name']));
check('db name is ums_edugate', ($config['database']['name'] ?? '') === 'ums_edugate');
check('stale_days default 180', (int)($config['verification']['stale_days'] ?? 0) === 180);

// 2. DB connectivity handled cleanly either way (no exception escapes).
$result = Database::testConnection();
check('testConnection returns structured result', isset($result['ok']));
check('DB failure path is graceful or success reported',
    $result['ok'] === true || isset($result['error']),
    $result['ok'] ? ('server v' . $result['version']) : 'not reachable (expected without MySQL)');

// 3. Routes file valid.
$routes = require dirname(__DIR__) . '/routes/web.php';
check('routes: GET / mapped',   isset($routes['GET']['/']));
check('routes: GET /health mapped', isset($routes['GET']['/health']));

// 4. Escaping helper.
check('e() escapes HTML', e('<script>') === '&lt;script&gt;');

echo $fail === 0 ? "\nALL CHECKS PASSED\n" : "\n$fail CHECK(S) FAILED\n";
exit($fail === 0 ? 0 : 1);
