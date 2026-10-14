<?php
/**
 * UMS EduGate — intakes list view (Phase 6).
 * @var array $rows @var int $total @var int $page @var int $per
 * @var string $search @var string $status @var int|null $universityId @var int|null $year
 * @var array $universities @var bool $canEdit
 */
require_once BASE_PATH . '/app/Core/View.php';
require_once BASE_PATH . '/app/Core/Csrf.php';
?>
<?= View::pageHeading(
    'Intakes',
    'Admission intakes per university and year. An intake with active application rounds cannot be archived.',
    $canEdit ? '/intakes/create' : '',
    'Add intake'
) ?>

<form class="row g-2 align-items-end mb-3" method="get" action="/intakes">
  <div class="col-12 col-md-4">
    <label class="form-label small text-secondary" for="q">Search</label>
    <input type="search" class="form-control form-control-sm" id="q" name="q"
           value="<?= e($search) ?>" placeholder="Intake name…">
  </div>
  <div class="col-6 col-md-3">
    <label class="form-label small text-secondary" for="university_id">University</label>
    <select class="form-select form-select-sm" id="university_id" name="university_id">
      <option value="">All universities</option>
      <?php foreach ($universities as $u): ?>
        <option value="<?= (int) $u['id'] ?>" <?= $universityId === (int) $u['id'] ? 'selected' : '' ?>><?= e((string) $u['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-2">
    <label class="form-label small text-secondary" for="year">Year</label>
    <input type="number" class="form-control form-control-sm" id="year" name="year" min="2000" max="2100"
           value="<?= $year !== null ? (int) $year : '' ?>" placeholder="Any">
  </div>
  <div class="col-6 col-md-2">
    <label class="form-label small text-secondary" for="status">Status</label>
    <select class="form-select form-select-sm" id="status" name="status">
      <?php foreach (['active' => 'Active', 'archived' => 'Archived', 'all' => 'All'] as $val => $lbl): ?>
        <option value="<?= e($val) ?>" <?= $status === $val ? 'selected' : '' ?>><?= e($lbl) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-1 d-grid gap-1">
    <button class="btn btn-sm btn-primary" type="submit">Go</button>
    <a class="btn btn-sm btn-outline-secondary" href="/intakes">Reset</a>
  </div>
</form>

<div class="card">
  <?php if ($rows === []): ?>
    <?= View::emptyState('No intakes found', 'Adjust filters or add the first intake.', 'calendar3') ?>
  <?php else: ?>
    <?php
    $head = '<tr><th>Intake</th><th>University</th><th>Year</th><th>Window</th><th>Confidence</th><th>Status</th>'
          . ($canEdit ? '<th scope="col" class="text-end">Actions</th>' : '') . '</tr>';
    $body = '';
    foreach ($rows as $r) {
        $archived = $r['archived_at'] !== null;
        $id = (int) $r['id'];
        $badgeVariant = match ((string) ($r['confidence_level'] ?? 'UNVERIFIED')) {
            'HIGH' => 'success', 'MEDIUM' => 'info', 'LOW' => 'warning', default => 'secondary',
        };
        $actions = '';
        if ($canEdit) {
            if (!$archived) {
                $actions .= '<a class="btn btn-sm btn-outline-primary" href="/intakes/' . $id . '/edit">Edit</a> ';
                $actions .= '<form method="post" action="/intakes/' . $id . '/archive" class="d-inline" data-confirm="Archive this intake?">'
                          . Csrf::field() . '<button class="btn btn-sm btn-outline-danger" type="submit">Archive</button></form>';
            } else {
                $actions .= '<form method="post" action="/intakes/' . $id . '/restore" class="d-inline">'
                          . Csrf::field() . '<button class="btn btn-sm btn-outline-success" type="submit">Restore</button></form>';
            }
        }
        $window = (($r['starts_on'] ?? '') ?: '?') . ' → ' . (($r['ends_on'] ?? '') ?: '?');
        $body .= '<tr' . ($archived ? ' class="opacity-50"' : '') . '>'
               . '<td><a href="/intakes/' . $id . '">' . e((string) $r['name']) . '</a></td>'
               . '<td><a href="/universities/' . (int) $r['university_id'] . '">' . e((string) $r['university_name']) . '</a></td>'
               . '<td>' . (int) $r['intake_year'] . '</td>'
               . '<td class="small">' . e($window) . '</td>'
               . '<td>' . View::badge((string) ($r['confidence_level'] ?? 'UNVERIFIED'), $badgeVariant) . '</td>'
               . '<td>' . ($archived ? View::badge('Archived', 'dark') : View::badge('Active', 'success')) . '</td>'
               . ($canEdit ? '<td class="text-end text-nowrap">' . $actions . '</td>' : '')
               . '</tr>';
    }
    echo View::table($head, $body);
    ?>
  <?php endif; ?>
</div>

<div class="d-flex justify-content-between align-items-center mt-3">
  <span class="small text-secondary"><?= (int) $total ?> record(s)</span>
  <?= View::pagination($page, $total, $per, '/intakes', array_filter([
      'q' => $search,
      'status'        => $status !== 'active' ? $status : '',
      'university_id' => $universityId !== null ? (string) $universityId : '',
      'year'          => $year !== null ? (string) $year : '',
  ])) ?>
</div>
