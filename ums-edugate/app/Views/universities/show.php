<?php
/**
 * UMS EduGate — university detail view (Phase 5).
 * @var array $university @var bool $canEdit
 */
require_once BASE_PATH . '/app/Core/View.php';
require_once BASE_PATH . '/app/Core/Csrf.php';

$archived = $university['archived_at'] !== null;
$id = (int) $university['id'];
$site = (string) ($university['website'] ?? '');
?>
<?= View::pageHeading(
    (string) $university['name'],
    ((string) $university['country_name']) . (($university['city'] ?? '') !== '' ? ' · ' . (string) $university['city'] : '')
      . ($archived ? ' · archived' : ''),
    $canEdit ? '/universities/' . $id . '/edit' : '',
    'Edit university'
) ?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="fw-semibold">Institution details</span>
    <span>
      <?= View::badge(str_replace('_', ' ', (string) $university['type']), 'primary') ?>
      <?= View::badge((string) ($university['confidence_level'] ?? 'UNVERIFIED'), match ((string) ($university['confidence_level'] ?? '')) { 'HIGH' => 'success', 'MEDIUM' => 'info', 'LOW' => 'warning', default => 'secondary' }) ?>
      <?php if ((int) ($university['is_sample'] ?? 0) === 1): ?><?= View::badge('sample data', 'light') ?><?php endif; ?>
    </span>
  </div>
  <div class="card-body">
    <dl class="row mb-0">
      <dt class="col-sm-3">Country</dt>
      <dd class="col-sm-9"><a href="/countries/<?= (int) $university['country_id'] ?>"><?= e((string) $university['country_name']) ?></a></dd>
      <dt class="col-sm-3">City</dt><dd class="col-sm-9"><?= e(($university['city'] ?? '') ?: '—') ?></dd>
      <dt class="col-sm-3">Website</dt>
      <dd class="col-sm-9"><?= $site !== '' ? '<a href="' . e($site) . '" target="_blank" rel="noopener noreferrer">' . e($site) . ' ↗</a>' : '—' ?></dd>
      <dt class="col-sm-3">Ranking note</dt><dd class="col-sm-9"><?= e(($university['ranking_note'] ?? '') ?: '—') ?></dd>
      <dt class="col-sm-3">Created</dt><dd class="col-sm-3"><?= e((string) $university['created_at']) ?></dd>
      <dt class="col-sm-3">Updated</dt><dd class="col-sm-3"><?= e((string) $university['updated_at']) ?></dd>
      <?php if (!empty($university['description'])): ?>
        <dt class="col-sm-3">Description</dt>
        <dd class="col-sm-9" style="white-space:pre-wrap"><?= e((string) $university['description']) ?></dd>
      <?php endif; ?>
    </dl>
  </div>
  <?php if ($canEdit): ?>
  <div class="card-footer bg-transparent d-flex gap-2">
    <?php if (!$archived): ?>
      <form method="post" action="/universities/<?= $id ?>/archive" data-confirm="Archive this university?">
        <?= Csrf::field() ?><button class="btn btn-sm btn-outline-danger" type="submit">Archive</button>
      </form>
    <?php else: ?>
      <form method="post" action="/universities/<?= $id ?>/restore">
        <?= Csrf::field() ?><button class="btn btn-sm btn-outline-success" type="submit">Restore</button>
      </form>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<p class="small text-secondary mt-3 mb-0">Programmes, intakes, fees and requirements for this institution arrive in later phases.</p>
