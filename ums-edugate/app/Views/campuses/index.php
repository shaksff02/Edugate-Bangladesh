<?php
/**
 * UMS EduGate — campuses list view (Phase 6).
 * @var array $rows @var int $total @var int $page @var int $per
 * @var string $search @var string $status @var int|null $universityId @var array $universities @var bool $canEdit
 */
require_once BASE_PATH . '/app/Core/View.php';
require_once BASE_PATH . '/app/Core/Csrf.php';
?>
<?= View::pageHeading(
    'Campuses',
    'Physical campuses linked to a university. A campus with active programmes cannot be archived.',
    $canEdit ? '/campuses/create' : '',
    'Add campus'
) ?>

<form class="row g-2 align-items-end mb-3" method="get" action="/campuses">
  <div class="col-12 col-md-4">
    <label class="form-label small text-secondary" for="q">Search</label>
    <input type="search" class="form-control form-control-sm" id="q" name="q"
           value="<?= e($search) ?>" placeholder="Campus name…">
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
    <label class="form-label small text-secondary" for="status">Status</label>
    <select class="form-select form-select-sm" id="status" name="status">
      <?php foreach (['active' => 'Active', 'archived' => 'Archived', 'all' => 'All'] as $val => $lbl): ?>
        <option value="<?= e($val) ?>" <?= $status === $val ? 'selected' : '' ?>><?= e($lbl) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-1 d-grid gap-1">
    <button class="btn btn-sm btn-primary" type="submit">Go</button>
    <a class="btn btn-sm btn-outline-secondary" href="/campuses">Reset</a>
  </div>
</form>

<div class="card">
  <?php if ($rows === []): ?>
    <?= View::emptyState('No campuses found', 'Adjust filters or add the first campus.', 'geo-alt') ?>
  <?php else: ?>
    <?php
    $head = '<th>Campus</th><th>University</th><th>City</th><th>Main</th><th>Confidence</th><th>Status</th>'
          . ($canEdit ? '<th scope="col" class="text-end">Actions</th>' : '');
    $head = '<tr>' . $head . '</tr>';
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
                $actions .= '<a class="btn btn-sm btn-outline-primary" href="/campuses/' . $id . '/edit">Edit</a> ';
                $actions .= '<form method="post" action="/campuses/' . $id . '/archive" class="d-inline" data-confirm="Archive this campus?">'
                          . Csrf::field() . '<button class="btn btn-sm btn-outline-danger" type="submit">Archive</button></form>';
            } else {
                $actions .= '<form method="post" action="/campuses/' . $id . '/restore" class="d-inline">'
                          . Csrf::field() . '<button class="btn btn-sm btn-outline-success" type="submit">Restore</button></form>';
            }
        }
        $body .= '<tr' . ($archived ? ' class="opacity-50"' : '') . '>'
               . '<td><a href="/campuses/' . $id . '">' . e((string) $r['name']) . '</a></td>'
               . '<td><a href="/universities/' . (int) $r['university_id'] . '">' . e((string) $r['university_name']) . '</a></td>'
               . '<td>' . e(($r['city'] ?? '') ?: '—') . '</td>'
               . '<td>' . ((int) ($r['is_main'] ?? 0) === 1 ? View::badge('Main', 'primary') : '—') . '</td>'
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
  <?= View::pagination($page, $total, $per, '/campuses', array_filter([
      'q' => $search,
      'status'         => $status !== 'active' ? $status : '',
      'university_id'  => $universityId !== null ? (string) $universityId : '',
  ])) ?>
</div>
