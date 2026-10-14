<?php
/**
 * UMS EduGate — application rounds list view (Phase 6).
 * @var array $rows @var int $total @var int $page @var int $per
 * @var string $search @var string $status @var int|null $universityId @var int|null $intakeId
 * @var array $universities @var array $intakes @var bool $canEdit
 */
require_once BASE_PATH . '/app/Core/View.php';
require_once BASE_PATH . '/app/Core/Csrf.php';
?>
<?= View::pageHeading(
    'Application rounds',
    'Rounds belong to an intake. Deadline labels: Upcoming · Closing soon (≤14 days) · Passed · Unknown (never guessed).',
    $canEdit ? '/rounds/create' : '',
    'Add round'
) ?>

<p class="mb-3"><a class="btn btn-sm btn-outline-primary" href="/rounds/deadlines">Deadline report</a></p>

<form class="row g-2 align-items-end mb-3" method="get" action="/rounds">
  <div class="col-12 col-md-3">
    <label class="form-label small text-secondary" for="q">Search</label>
    <input type="search" class="form-control form-control-sm" id="q" name="q"
           value="<?= e($search) ?>" placeholder="Round name…">
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
  <div class="col-6 col-md-3">
    <label class="form-label small text-secondary" for="intake_id">Intake</label>
    <select class="form-select form-select-sm" id="intake_id" name="intake_id">
      <option value="">All intakes</option>
      <?php foreach ($intakes as $i): ?>
        <option value="<?= (int) $i['id'] ?>" <?= $intakeId === (int) $i['id'] ? 'selected' : '' ?>>
          <?= e((string) $i['name'] . ' ' . (int) $i['intake_year']) ?>
        </option>
      <?php endforeach; ?>
    </select>
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
    <a class="btn btn-sm btn-outline-secondary" href="/rounds">Reset</a>
  </div>
</form>

<div class="card">
  <?php if ($rows === []): ?>
    <?= View::emptyState('No application rounds found', 'Adjust filters or add the first round.', 'hourglass-split') ?>
  <?php else: ?>
    <?php
    $head = '<tr><th>Round</th><th>Intake</th><th>University</th><th>Opens</th><th>Closes</th><th>Deadline</th><th>Status</th>'
          . ($canEdit ? '<th scope="col" class="text-end">Actions</th>' : '') . '</tr>';
    $body = '';
    foreach ($rows as $r) {
        $archived = $r['archived_at'] !== null;
        $id = (int) $r['id'];
        $d = $r['deadline'] ?? ['label' => 'Unknown', 'css' => 'light text-body-secondary border', 'days' => null];
        $actions = '';
        if ($canEdit) {
            if (!$archived) {
                $actions .= '<a class="btn btn-sm btn-outline-primary" href="/rounds/' . $id . '/edit">Edit</a> ';
                $actions .= '<form method="post" action="/rounds/' . $id . '/archive" class="d-inline" data-confirm="Archive this round?">'
                          . Csrf::field() . '<button class="btn btn-sm btn-outline-danger" type="submit">Archive</button></form>';
            } else {
                $actions .= '<form method="post" action="/rounds/' . $id . '/restore" class="d-inline">'
                          . Csrf::field() . '<button class="btn btn-sm btn-outline-success" type="submit">Restore</button></form>';
            }
        }
        $deadlineBadge = View::badge($d['label'] . ($d['days'] !== null ? ' (' . abs((int) $d['days']) . 'd)' : ''), $d['css']);
        $body .= '<tr' . ($archived ? ' class="opacity-50"' : '') . '>'
               . '<td><a href="/rounds/' . $id . '">' . e((string) $r['name']) . '</a></td>'
               . '<td><a href="/intakes/' . (int) $r['intake_id'] . '">' . e((string) $r['intake_name'] . ' ' . (int) $r['intake_year']) . '</a></td>'
               . '<td><a href="/universities/' . (int) $r['university_id'] . '">' . e((string) $r['university_name']) . '</a></td>'
               . '<td class="small">' . e(($r['opens_at'] ?? '') ?: '—') . '</td>'
               . '<td class="small">' . e(($r['closes_at'] ?? '') ?: '—') . '</td>'
               . '<td>' . $deadlineBadge . '</td>'
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
  <?= View::pagination($page, $total, $per, '/rounds', array_filter([
      'q' => $search,
      'status'        => $status !== 'active' ? $status : '',
      'university_id' => $universityId !== null ? (string) $universityId : '',
      'intake_id'     => $intakeId !== null ? (string) $intakeId : '',
  ])) ?>
</div>
