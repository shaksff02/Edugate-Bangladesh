<?php // Dashboard placeholder — real KPIs arrive with later phases. ?>
<?= View::pageHeading('Dashboard', 'Welcome back, ' . e((string) ($userName ?? 'user')) . '. Role: ' . e((string) ($role ?? '—')) . '.') ?>

<div class="row g-3 mb-4">
  <?php foreach ([
      ['Universities', 'building'],
      ['Countries', 'globe-americas'],
      ['Students', 'people'],
      ['Applications', 'card-listing'],
  ] as [$label, $icon]): ?>
    <div class="col-6 col-lg-3">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <i class="bi bi-<?= e($icon) ?> fs-3 text-secondary" aria-hidden="true"></i>
            <?= View::badge('Soon', 'secondary') ?>
          </div>
          <div class="mt-2 small text-secondary"><?= e($label) ?></div>
          <div class="h4 mb-0 text-secondary">—</div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="card shadow-sm">
  <div class="card-header fw-semibold">Recent logins</div>
  <div class="card-body p-0">
    <?php if (empty($recentLogins)): ?>
      <?= View::emptyState('No login activity recorded yet.', 'Attempts appear here once staff sign in.', 'clock-history') ?>
    <?php else: ?>
      <?php $rows = ''; foreach ($recentLogins as $r): ?>
        <tr>
          <td class="ps-3"><?= e((string) $r['email_attempted']) ?></td>
          <td><?= e((string) ($r['ip_address'] ?? '—')) ?></td>
          <td><?= !empty($r['success']) ? View::badge('Success', 'success') : View::badge('Failed', 'danger') ?></td>
          <td><?= e((string) $r['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?= View::table(
        '<tr><th scope="col">Email attempted</th><th scope="col">IP</th><th scope="col">Result</th><th scope="col">When</th></tr>',
        $rows) ?>
    <?php endif; ?>
  </div>
</div>
