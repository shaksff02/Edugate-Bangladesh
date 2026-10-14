<?php
/**
 * UMS EduGate — campus detail view (Phase 6).
 * @var array $campus @var bool $canEdit
 */
require_once BASE_PATH . '/app/Core/View.php';
require_once BASE_PATH . '/app/Core/Csrf.php';

$archived = $campus['archived_at'] !== null;
$id = (int) $campus['id'];
?>
<?= View::pageHeading(
    (string) $campus['name'],
    ((string) $campus['university_name'])
      . (($campus['city'] ?? '') !== '' ? ' · ' . (string) $campus['city'] : '')
      . ($archived ? ' · archived' : ''),
    $canEdit ? '/campuses/' . $id . '/edit' : '',
    'Edit campus'
) ?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="fw-semibold">Campus details</span>
    <span>
      <?php if ((int) ($campus['is_main'] ?? 0) === 1): ?><?= View::badge('Main campus', 'primary') ?><?php endif; ?>
      <?= View::badge((string) ($campus['confidence_level'] ?? 'UNVERIFIED'), match ((string) ($campus['confidence_level'] ?? '')) { 'HIGH' => 'success', 'MEDIUM' => 'info', 'LOW' => 'warning', default => 'secondary' }) ?>
    </span>
  </div>
  <div class="card-body">
    <dl class="row mb-0">
      <dt class="col-sm-3">University</dt>
      <dd class="col-sm-9"><a href="/universities/<?= (int) $campus['university_id'] ?>"><?= e((string) $campus['university_name']) ?></a></dd>
      <dt class="col-sm-3">City</dt><dd class="col-sm-9"><?= e(($campus['city'] ?? '') ?: '—') ?></dd>
      <dt class="col-sm-3">Address</dt><dd class="col-sm-9"><?= e(($campus['address'] ?? '') ?: '—') ?></dd>
      <dt class="col-sm-3">Created</dt><dd class="col-sm-3"><?= e((string) $campus['created_at']) ?></dd>
      <dt class="col-sm-3">Updated</dt><dd class="col-sm-3"><?= e((string) $campus['updated_at']) ?></dd>
    </dl>
  </div>
  <?php if ($canEdit): ?>
  <div class="card-footer bg-transparent d-flex gap-2">
    <?php if (!$archived): ?>
      <form method="post" action="/campuses/<?= $id ?>/archive" data-confirm="Archive this campus?">
        <?= Csrf::field() ?><button class="btn btn-sm btn-outline-danger" type="submit">Archive</button>
      </form>
    <?php else: ?>
      <form method="post" action="/campuses/<?= $id ?>/restore">
        <?= Csrf::field() ?><button class="btn btn-sm btn-outline-success" type="submit">Restore</button>
      </form>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<p class="small text-secondary mt-3 mb-0">Programmes attached to this campus arrive in Phase 7.</p>
