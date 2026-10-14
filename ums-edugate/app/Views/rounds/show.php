<?php
/**
 * UMS EduGate — application-round detail view (Phase 6).
 * @var array $round @var bool $canEdit
 */
require_once BASE_PATH . '/app/Core/View.php';
require_once BASE_PATH . '/app/Core/Csrf.php';

$archived = $round['archived_at'] !== null;
$id = (int) $round['id'];
$d = $round['deadline'] ?? ['label' => 'Unknown', 'css' => 'light text-body-secondary border', 'days' => null];
?>
<?= View::pageHeading(
    (string) $round['name'],
    ((string) $round['intake_name']) . ' ' . (int) $round['intake_year'] . ' · ' . (string) $round['university_name']
      . ($archived ? ' · archived' : ''),
    $canEdit ? '/rounds/' . $id . '/edit' : '',
    'Edit round'
) ?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="fw-semibold">Round details</span>
    <span>
      <?= View::badge('Deadline: ' . $d['label'], $d['css']) ?>
      <?= View::badge((string) ($round['confidence_level'] ?? 'UNVERIFIED'), match ((string) ($round['confidence_level'] ?? '')) { 'HIGH' => 'success', 'MEDIUM' => 'info', 'LOW' => 'warning', default => 'secondary' }) ?>
    </span>
  </div>
  <div class="card-body">
    <dl class="row mb-0">
      <dt class="col-sm-3">Intake</dt>
      <dd class="col-sm-9"><a href="/intakes/<?= (int) $round['intake_id'] ?>"><?= e((string) $round['intake_name'] . ' ' . (int) $round['intake_year']) ?></a></dd>
      <dt class="col-sm-3">University</dt>
      <dd class="col-sm-9"><a href="/universities/<?= (int) $round['university_id'] ?>"><?= e((string) $round['university_name']) ?></a></dd>
      <dt class="col-sm-3">Opens at</dt><dd class="col-sm-3"><?= e(($round['opens_at'] ?? '') ?: '—') ?></dd>
      <dt class="col-sm-3">Closes at</dt><dd class="col-sm-3"><?= e(($round['closes_at'] ?? '') ?: '— (unknown)') ?></dd>
      <dt class="col-sm-3">Result date</dt><dd class="col-sm-3"><?= e(($round['result_date'] ?? '') ?: '—') ?></dd>
      <dt class="col-sm-3">Created</dt><dd class="col-sm-3"><?= e((string) $round['created_at']) ?></dd>
      <dt class="col-sm-3">Updated</dt><dd class="col-sm-3"><?= e((string) $round['updated_at']) ?></dd>
    </dl>
  </div>
  <?php if ($canEdit): ?>
  <div class="card-footer bg-transparent d-flex gap-2">
    <?php if (!$archived): ?>
      <form method="post" action="/rounds/<?= $id ?>/archive" data-confirm="Archive this round?">
        <?= Csrf::field() ?><button class="btn btn-sm btn-outline-danger" type="submit">Archive</button>
      </form>
    <?php else: ?>
      <form method="post" action="/rounds/<?= $id ?>/restore">
        <?= Csrf::field() ?><button class="btn btn-sm btn-outline-success" type="submit">Restore</button>
      </form>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>
