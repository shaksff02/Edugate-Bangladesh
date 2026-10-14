<?php
/**
 * Phase 3 UI shell verification (run: php tests/ui_shell_test.php)
 * Checks PRD acceptance criteria without needing a browser.
 */
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/app/Views/layouts/main.php';
require_once BASE_PATH . '/app/Core/View.php';

$pass = 0; $fail = 0;
function check(string $name, bool $ok): void {
    global $pass, $fail;
    if ($ok) { $pass++; echo "PASS  $name\n"; }
    else     { $fail++; echo "FAIL  $name\n"; }
}

// 1. Navigation registry: no dead links presented as completed features.
$nav = View::navigation();
$flat = [];
foreach ($nav as $group => $items) foreach ($items as $i) $flat[] = $i + ['group' => $group];
check('nav has groups & items', count($flat) >= 8);
check('nav states valid', array_reduce($flat, fn($c, $i) => $c && in_array($i['state'], ['ready','coming_soon','hidden'], true), true));
$readyUrls = array_column(array_filter($flat, fn($i) => $i['state'] === 'ready'), 'url');
$routes = require BASE_PATH . '/routes/web.php';
foreach ($readyUrls as $u) check("ready link '$u' resolves to a route", isset($routes['GET'][$u]) || isset($routes['POST'][$u]));

// 2. Components escape output (XSS safety).
check('alert escapes HTML', str_contains(View::alert('<script>x</script>'), '&lt;script&gt;'));
check('badge escapes HTML', str_contains(View::badge('<b>'), '&lt;b&gt;'));
check('field value escapes', str_contains(View::field(['label'=>'L','name'=>'n','value'=>'" onload="evil']), '&quot;'));
check('breadcrumb escapes', str_contains(View::breadcrumb([['label'=>'<i>','url'=>'/x']]), '&lt;i&gt;'));

// 3. Pagination math.
$p = View::pagination(2, 95, 20, '/list');
check('pagination renders pages', str_contains($p, '>5<') && str_contains($p, 'rel="prev"') && str_contains($p, 'rel="next"'));
check('pagination hides single page', View::pagination(1, 10, 20, '/l') === '');
check('pagination keeps query', str_contains(View::pagination(1, 40, 10, '/l', ['q'=>'abc']), 'q=abc&amp;page=2'));

// 4. Form components have labels bound by id (accessibility).
$f = View::field(['label'=>'Email','name'=>'email','type'=>'email','required'=>true]);
check('field label-for matches input id', (bool)preg_match('/for="email".*id="email"/s', $f));
check('field error shown', str_contains(View::field(['label'=>'a','name'=>'b','error'=>'bad']), 'invalid-feedback'));

// 5. States exist.
check('empty state', str_contains(View::emptyState('Nothing here'), 'Nothing here'));
check('loading state', str_contains(View::loadingState(), 'spinner-border'));
check('error state retry', str_contains(View::errorState('Boom', '/retry'), 'href="/retry"'));

// 6. Shells render end-to-end via HTTP (started separately) — checked here only for file structure.
check('header/footer exist', is_readable(BASE_PATH.'/app/Views/layouts/header.php') && is_readable(BASE_PATH.'/app/Views/layouts/footer.php'));
check('print stylesheet present', str_contains(file_get_contents(BASE_PATH.'/public/assets/css/app.css'), '@media print'));
check('app.js present', is_readable(BASE_PATH.'/public/assets/js/app.js'));

echo "\nRESULT: $pass passed, $fail failed\n";
exit($fail === 0 ? 0 : 1);
