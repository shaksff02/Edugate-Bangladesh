<?php // System status page — rendered inside the authenticated app shell. ?>
<?= View::pageHeading('System status', 'Environment health for UMS EduGate. All values computed at request time.') ?>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card shadow-sm mb-3">
      <div class="card-header fw-semibold">Environment</div>
      <div class="card-body p-0">
        <?= View::table(
          '<tr><th scope="col" class="w-25">Item</th><th scope="col">Status</th></tr>',
          '<tr><th scope="row" class="ps-3">PHP version</th><td>' . e(PHP_VERSION) . ' (target: 8.1+)</td></tr>'
        . '<tr><th scope="row" class="ps-3">PDO MySQL driver</th><td>'
        . (extension_loaded('pdo_mysql') ? View::badge('installed', 'success') : View::badge('missing — enable pdo_mysql', 'danger'))
        . '</td></tr>'
        . '<tr><th scope="row" class="ps-3">Database (<code>ums_edugate</code>)</th><td>'
        . (!empty($dbTest['ok'])
            ? View::badge('reachable', 'success') . ' <span class="ms-2 text-secondary small">v' . e($dbTest['version'] ?? '') . ' · ' . e((string)($dbTest['ms'] ?? '-')) . ' ms</span>'
            : View::badge('not reachable', 'warning') . ' <div class="small text-secondary mt-1">' . e($dbTest['error'] ?? '') . '</div>')
        . '</td></tr>'
        . '<tr><th scope="row" class="ps-3">Config source</th><td>'
        . (is_readable(BASE_PATH . '/config/config.local.php')
            ? View::badge('config.local.php (private)', 'success')
            : View::badge('config.example.php (defaults)', 'secondary'))
        . '</td></tr>'
        ); ?>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-header fw-semibold">Phase roadmap</div>
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between align-items-center">1 · Foundation <?= View::badge('Done', 'success') ?></li>
        <li class="list-group-item d-flex justify-content-between align-items-center">2 · Schema, migrations &amp; seed <?= View::badge('Done', 'success') ?></li>
        <li class="list-group-item d-flex justify-content-between align-items-center">3 · Shared UI shell <?= View::badge('This phase', 'primary') ?></li>
        <li class="list-group-item d-flex justify-content-between align-items-center">4 · Authentication &amp; RBAC <?= View::badge('Core done', 'info') ?></li>
        <li class="list-group-item text-secondary">5–15 · Business modules, QA, release <?= View::badge('Planned', 'light') ?></li>
      </ul>
    </div>
  </div>
</div>
