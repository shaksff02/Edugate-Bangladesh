<?php // Phase 1 foundation status page — no business data or features yet. ?>
<div class="row justify-content-center">
  <div class="col-lg-8">
    <h1 class="h3 mb-1"><?= e($title) ?></h1>
    <p class="text-secondary">Project foundation is in place. Business modules arrive in later phases per the PRD.</p>

    <div class="card mb-3 shadow-sm">
      <div class="card-header fw-semibold">Environment</div>
      <div class="card-body">
        <table class="table table-sm mb-0">
          <tbody>
            <tr><th scope="row" class="w-25">PHP version</th><td><?= e(PHP_VERSION) ?> (target: 8.1+)</td></tr>
            <tr><th scope="row">PDO MySQL driver</th><td>
              <?php if (extension_loaded('pdo_mysql')): ?>
                <span class="badge text-bg-success">installed</span>
              <?php else: ?>
                <span class="badge text-bg-danger">missing — enable pdo_mysql</span>
              <?php endif; ?>
            </td></tr>
            <tr><th scope="row">Database (<code>ums_edugate</code>)</th><td>
              <?php if (!empty($dbTest['ok'])): ?>
                <span class="badge text-bg-success">reachable</span>
                <span class="ms-2 text-secondary small">v<?= e($dbTest['version']) ?> · <?= e((string)($dbTest['ms'] ?? '-')) ?> ms</span>
              <?php else: ?>
                <span class="badge text-bg-secondary">not configured yet</span>
                <div class="small text-secondary mt-1"><?= e($dbTest['error'] ?? 'Expected in Phase 1 — schema is created in Phase 2.') ?></div>
              <?php endif; ?>
            </td></tr>
            <tr><th scope="row">Config source</th><td>
              <?php $local = is_readable(__DIR__ . '/../../../config/config.local.php'); ?>
              <span class="badge text-bg-<?= $local ? 'success' : 'secondary' ?>"><?= $local ? 'config.local.php (private)' : 'config.example.php (defaults)' ?></span>
            </td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-header fw-semibold">Phase roadmap</div>
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between align-items-center">
          1 · Repository inspection &amp; project foundation <span class="badge text-bg-success">This phase</span>
        </li>
        <li class="list-group-item text-secondary">2 · MySQL schema, migrations and demo seed <span class="badge text-bg-light">Planned</span></li>
        <li class="list-group-item text-secondary">3–15 · UI shell, auth, modules, QA <span class="badge text-bg-light">Planned</span></li>
      </ul>
    </div>

    <p class="small text-secondary mt-3 mb-0">
      Note: all values shown are computed at request time; nothing here is hardcoded sample data.
    </p>
  </div>
</div>
