<?php
/**
 * UMS EduGate — intake detail view (Phase 6).
 * @var array $intake @var bool $canEdit
 */
require_once BASE_PATH . '/app/Core/View.php';
require_once BASE_PATH . '/app/Core/Csrf.php';

$archived = $intake['archived_at'] !== null;
$id = (int) $intake['id'];
?>
<?= View::pageHeading(
    (string) $intake['name'] . ' ' . (int) $intake['intake_year'],
    ((string) $intake['university_name']) . ($archived ? ' · archived' : ''),
    $canEdit ? '/intakes/' . $id . '/edit' : '',
    'Edit intake'
) ?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="fw-semibold">Intake details</span>
    <?= View::badge((string) ($intake['confidence_level'] ?? 'UNVERIFIED'), match ((string) ($intake['confidence_level'] ?? '')) { 'HIGH' => 'success', 'MEDIUM' => 'info', 'LOW' => 'warning', default => 'secondary' }) ?>
  </div>
  <div class="card-body">
    <dl class="row mb-0">
      <dt class="col-sm-3">University</dt>
      <dd class="col-sm-9"><a href="/universities/<?= (int) $intake['university_id'] ?>"><?= e((string) $intake['university_name']) ?></a></dd>
      <dt class="col-sm-3">Year</dt><dd class="col-sm-3"><?= (int) $intake['intake_year'] ?></dd>
      <dt class="col-sm-3">Starts on</dt><dd class="col-sm-3"><?= e(($intake['starts_on'] ?? '') ?: '—') ?></dd>
      <dt class="col-sm-3">Ends on</dt><dd class="col-sm-3"><?= e(($intake['ends_on'] ?? '') ?: '—') ?></dd>
      <dt class="col-sm-3">Created</dt><dd class="col-sm-3"><?= e((string) $intake['created_at']) ?></dd>
      <dt class="col-sm-3">Updated</dt><dd class="col-sm-3"><?= e((string) $intake['updated_at']) ?></dd>
    </dl>
  </div>
  <?php if ($canEdit): ?>
  <div class="card-footer bg-transparent d-flex gap-2">
    <?php if (!$archived): ?>
      <form method="post" action="/intakes/<?= $id ?>/archive" data-confirm="Archive this intake?">
        <?= Csrf::field() ?><button class="btn btn-sm btn-outline-danger" type="submit">Archive</button>
      </form>
    <?php else: ?>
      <form method="post" action="/intakes/<?= $id ?>/restore">
        <?= Csrf::field() ?><button class="btn btn-sm btn-outline-success" type="submit">Restore</button>
      </form>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<p class="small text-secondary mt-3 mb-0">
  <a href="/rounds?intake_id=<?= $id ?>">View application rounds for this intake →</a>
</p>
