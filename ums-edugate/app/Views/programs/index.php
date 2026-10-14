<?php
/**
 * UMS EduGate — programs list view (Phase 7).
 * @var array $rows @var int $total @var int $page @var int $per
 * @var array $filters @var array $countries @var array $universities @var array $campuses @var bool $canEdit
 */
require_once BASE_PATH . '/app/Core/View.php';
require_once BASE_PATH . '/app/Core/Csrf.php';
require_once BASE_PATH . '/app/Repositories/ProgramRepository.php';
?>
<?= View::pageHeading(
    'Programs',
    'Academic catalog. A program must reference an existing, active university; its campus (if set) must belong to that university.',
    $canEdit ? '/programs/create' : '',
    'Add program'
) ?>

<form class="row g-2 align-items-end mb-3" method="get" action="/programs">
  <div class="col-12 col-md-4">
    <label class="form-label small text-secondary" for="q">Search</label>
    <input type="search" class="form-control form-control-sm" id="q" name="q"
           value="<?= e($filters['q']) ?>" placeholder="Program name or field…">
  </div>
  <div class="col-6 col-md-3">
    <label class="form-label small text-secondary" for="country_id">Country</label>
    <select class="form-select form-select-sm" id="country_id" name="country_id">
      <option value="">All countries</option>
      <?php foreach ($countries as $c): ?>
        <option value="<?= (int) $c['id'] ?>" <?= (int) ($filters['country_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= e((string) $c['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-3">
    <label class="form-label small text-secondary" for="university_id">University</label>
    <select class="form-select form-select-sm" id="university_id" name="university_id">
      <option value="">All universities</option>
      <?php foreach ($universities as $u): ?>
        <option value="<?= (int) $u['id'] ?>" <?= (int) ($filters['university_id'] ?? 0) === (int) $u['id'] ? 'selected' : '' ?>><?= e((string) $u['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-2">
    <label class="form-label small text-secondary" for="campus_id">Campus</label>
    <select class="form-select form-select-sm" id="campus_id" name="campus_id">
      <option value="">All campuses</option>
      <?php foreach ($campuses as $cp): ?>
        <option value="<?= (int) $cp['id'] ?>" <?= (int) ($filters['campus_id'] ?? 0) === (int) $cp['id'] ? 'selected' : '' ?>><?= e((string) $cp['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-2">
    <label class="form-label small text-secondary" for="level">Degree level</label>
    <select class="form-select form-select-sm" id="level" name="level">
      <option value="">All levels</option>
      <?php foreach (ProgramRepository::LEVELS as $lvl): ?>
        <option value="<?= e($lvl) ?>" <?= $filters['level'] === $lvl ? 'selected' : '' ?>><?= e($lvl) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-2">
    <label class="form-label small text-secondary" for="mode">Study mode</label>
    <select class="form-select form-select-sm" id="mode" name="mode">
      <option value="">Any mode</option>
      <?php foreach (ProgramRepository::MODES as $val => $lbl): ?>
        <option value="<?= e($val) ?>" <?= $filters['mode'] === $val ? 'selected' : '' ?>><?= e($lbl) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-2">
    <label class="form-label small text-secondary" for="major">Major / field</label>
    <input type="search" class="form-control form-control-sm" id="major" name="major"
           value="<?= e($filters['major']) ?>" placeholder="e.g. Computer Science">
  </div>
  <div class="col-6 col-md-2">
    <label class="form-label small text-secondary" for="language">Language</label>
    <input type="search" class="form-control form-control-sm" id="language" name="language"
           value="<?= e($filters['language']) ?>" placeholder="e.g. English">
  </div>
  <div class="col-6 col-md-2">
    <label class="form-label small text-secondary" for="status">Status</label>
    <select class="form-select form-select-sm" id="status" name="status">
      <?php foreach (['active' => 'Active', 'archived' => 'Archived', 'all' => 'All'] as $val => $lbl): ?>
        <option value="<?= e($val) ?>" <?= $filters['status'] === $val ? 'selected' : '' ?>><?= e($lbl) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-2 d-grid gap-1">
    <button class="btn btn-sm btn-primary" type="submit">Apply filters</button>
    <a class="btn btn-sm btn-outline-secondary" href="/programs">Reset</a>
  </div>
</form>

<div class="card">
  <?php if ($rows === []): ?>
    <?= View::emptyState('No programs found', 'Adjust the combined filters or add the first program.', 'mortarboard') ?>
  <?php else: ?>
    <?php
    $head = '<th>Program</th><th>University</th><th>Country</th><th>Level</th><th>Mode</th>'
          . '<th>Duration</th><th>Credits</th><th>Language</th><th>Status</th>'
          . ($canEdit ? '<th scope="col" class="text-end">Actions</th>' : '');
    $head = '<tr>' . $head . '</tr>';
    $body = '';
    foreach ($rows as $r) {
        $archived = $r['archived_at'] !== null;
        $id       = (int) $r['id'];
        $notProvided = static fn($v): string => ($v === null || $v === '') ? 'Not provided' : e((string) $v);
        $actions = '';
        if ($canEdit) {
            if (!$archived) {
                $actions .= '<a class="btn btn-sm btn-outline-primary" href="/programs/' . $id . '/edit">Edit</a> ';
                $actions .= '<form method="post" action="/programs/' . $id . '/archive" class="d-inline" data-confirm="Archive this program?">'
                          . Csrf::field() . '<button class="btn btn-sm btn-outline-danger" type="submit">Archive</button></form>';
            } else {
                $actions .= '<form method="post" action="/programs/' . $id . '/restore" class="d-inline">'
                          . Csrf::field() . '<button class="btn btn-sm btn-outline-success" type="submit">Restore</button></form>';
            }
        }
        $body .= '<tr' . ($archived ? ' class="opacity-50"' : '') . '>'
               . '<td><a href="/programs/' . $id . '">' . e((string) $r['name']) . '</a>'
               .  (($r['field'] ?? '') !== null && $r['field'] !== '' ? '<div class="small text-secondary">' . e((string) $r['field']) . '</div>' : '')
               . '</td>'
               . '<td><a href="/universities/' . (int) $r['university_id'] . '">' . e((string) $r['university_name']) . '</a></td>'
               . '<td>' . e((string) ($r['country_name'] ?? '—')) . '</td>'
               . '<td>' . View::badge((string) $r['level'], 'info') . '</td>'
               . '<td>' . e(ProgramRepository::MODES[(string) ($r['study_mode'] ?? 'ONCAMPUS')] ?? 'On-campus') . '</td>'
               . '<td>' . ($r['duration_months'] !== null ? (int) $r['duration_months'] . ' mo' : 'Not provided') . '</td>'
               . '<td>' . ($r['credits'] !== null ? (int) $r['credits'] : 'Not provided') . '</td>'
               . '<td>' . $notProvided($r['language_of_instruction'] ?? null) . '</td>'
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
  <?= View::pagination($page, $total, $per, '/programs', array_filter([
      'q'             => $filters['q'],
      'status'        => $filters['status'] !== 'active' ? $filters['status'] : '',
      'country_id'    => $filters['country_id'] !== null ? (string) $filters['country_id'] : '',
      'university_id' => $filters['university_id'] !== null ? (string) $filters['university_id'] : '',
      'campus_id'     => $filters['campus_id'] !== null ? (string) $filters['campus_id'] : '',
      'level'         => $filters['level'],
      'major'         => $filters['major'],
      'mode'          => $filters['mode'],
      'language'      => $filters['language'],
  ])) ?>
</div>
