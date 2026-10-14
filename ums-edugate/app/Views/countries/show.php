<?php
/**
 * UMS EduGate — country detail view (Phase 5).
 * @var array $country @var array $unis @var bool $canEdit
 */
require_once BASE_PATH . '/app/Core/View.php';
require_once BASE_PATH . '/app/Core/Csrf.php';

$archived = $country['archived_at'] !== null;
$id = (int) $country['id'];
?>
<?= View::pageHeading(
    (string) $country['name'],
    ($archived ? 'Archived' : 'Active') . ' country record'
      . ((int) ($country['is_sample'] ?? 0) === 1 ? ' · demo seed data' : ''),
    $canEdit ? '/countries/' . $id . '/edit' : '',
    'Edit country'
) ?>

<div class="row g-3">
  <div class="col-12 col-lg-5">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Details</span>
        <?= View::badge((string) $country['confidence_level'], match ((string) $country['confidence_level']) { 'HIGH' => 'success', 'MEDIUM' => 'info', 'LOW' => 'warning', default => 'secondary' }) ?>
      </div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-4">ISO codes</dt><dd class="col-8"><?= e(($country['iso2'] ?? '') ?: '—') ?> / <?= e(($country['iso3'] ?? '') ?: '—') ?></dd>
          <dt class="col-4">Region</dt><dd class="col-8"><?= e(($country['region'] ?? '') ?: '—') ?></dd>
          <dt class="col-4">Currency</dt><dd class="col-8"><?= e(($country['currency_code'] ?? '') ?: '—') ?></dd>
          <dt class="col-4">Status</dt><dd class="col-8"><?= $archived ? View::badge('Archived', 'dark') : View::badge('Active', 'success') ?></dd>
          <dt class="col-4">Created</dt><dd class="col-8"><?= e((string) $country['created_at']) ?></dd>
          <dt class="col-4">Updated</dt><dd class="col-8"><?= e((string) $country['updated_at']) ?></dd>
        </dl>
        <?php if (!empty($country['visa_notes'])): ?>
          <hr><h2 class="h6">Visa notes</h2>
          <p class="mb-0" style="white-space:pre-wrap"><?= e((string) $country['visa_notes']) ?></p>
        <?php endif; ?>
      </div>
      <?php if ($canEdit): ?>
      <div class="card-footer bg-transparent d-flex gap-2">
        <?php if (!$archived): ?>
          <form method="post" action="/countries/<?= $id ?>/archive" data-confirm="Archive this country?">
            <?= Csrf::field() ?><button class="btn btn-sm btn-outline-danger" type="submit">Archive</button>
          </form>
        <?php else: ?>
          <form method="post" action="/countries/<?= $id ?>/restore">
            <?= Csrf::field() ?><button class="btn btn-sm btn-outline-success" type="submit">Restore</button>
          </form>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-12 col-lg-7">
    <div class="card h-100">
      <div class="card-header fw-semibold">Universities in <?= e((string) $country['name']) ?> (<?= count($unis) ?>)</div>
      <?php if ($unis === []): ?>
        <?= View::emptyState('No universities yet', 'Add one from the Universities page.', 'building') ?>
      <?php else: ?>
        <?php
        $head = '<tr><th>Name</th><th>City</th><th>Type</th><th>Status</th></tr>';
        $body = '';
        foreach ($unis as $u) {
            $body .= '<tr>'
                  . '<td><a href="/universities/' . (int) $u['id'] . '">' . e((string) $u['name']) . '</a></td>'
                  . '<td>' . e(($u['city'] ?? '') ?: '—') . '</td>'
                  . '<td>' . e(str_replace('_', ' ', (string) $u['type'])) . '</td>'
                  . '<td>' . ($u['archived_at'] !== null ? View::badge('Archived', 'dark') : View::badge('Active', 'success')) . '</td>'
                  . '</tr>';
        }
        echo View::table($head, $body);
        ?>
      <?php endif; ?>
    </div>
  </div>
</div>
