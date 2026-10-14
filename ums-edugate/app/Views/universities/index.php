<?php
/**
 * UMS EduGate — universities list view (Phase 5).
 * @var array $rows @var int $total @var int $page @var int $per
 * @var string $search @var string $status @var int|null $countryId @var string $type
 * @var array $countries @var bool $canEdit
 */
require_once BASE_PATH . '/app/Core/View.php';
require_once BASE_PATH . '/app/Core/Csrf.php';
?>
<?= View::pageHeading(
    'Universities',
    'Institution records linked to a country. Duplicate names within the same country are rejected at save time.',
    $canEdit ? '/universities/create' : '',
    'Add university'
) ?>

<form class="row g-2 align-items-end mb-3" method="get" action="/universities">
  <div class="col-12 col-md-4">
    <label class="form-label small text-secondary" for="q">Search</label>
    <input type="search" class="form-control form-control-sm" id="q" name="q"
           value="<?= e($search) ?>" placeholder="Name or city…">
  </div>
  <div class="col-6 col-md-3">
    <label class="form-label small text-secondary" for="country_id">Country</label>
    <select class="form-select form-select-sm" id="country_id" name="country_id">
      <option value="">All countries</option>
      <?php foreach ($countries as $c): ?>
        <option value="<?= (int) $c['id'] ?>" <?= $countryId === (int) $c['id'] ? 'selected' : '' ?>><?= e((string) $c['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-2">
    <label class="form-label small text-secondary" for="type">Type</label>
    <select class="form-select form-select-sm" id="type" name="type">
      <option value="">Any</option>
      <?php foreach (\MasterDataRepository::UNI_TYPES as $t): ?>
        <option value="<?= e($t) ?>" <?= strcasecmp($type, $t) === 0 ? 'selected' : '' ?>><?= e(str_replace('_', ' ', $t)) ?></option>
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
    <a class="btn btn-sm btn-outline-secondary" href="/universities">Reset</a>
  </div>
</form>

<div class="card">
  <?php if ($rows === []): ?>
    <?= View::emptyState('No universities found', 'Adjust filters or add the first university.', 'building') ?>
  <?php else: ?>
    <?php
    $head = '<tr><th>Name</th><th>Country</th><th>City</th><th>Type</th><th>Website</th>'
          . '<th>Confidence</th><th>Status</th>' . ($canEdit ? '<th scope="col" class="text-end">Actions</th>' : '') . '</tr>';
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
                $actions .= '<a class="btn btn-sm btn-outline-primary" href="/universities/' . $id . '/edit">Edit</a> ';
                $actions .= '<form method="post" action="/universities/' . $id . '/archive" class="d-inline" data-confirm="Archive this university?">'
                          . Csrf::field() . '<button class="btn btn-sm btn-outline-danger" type="submit">Archive</button></form>';
            } else {
                $actions .= '<form method="post" action="/universities/' . $id . '/restore" class="d-inline">'
                          . Csrf::field() . '<button class="btn btn-sm btn-outline-success" type="submit">Restore</button></form>';
            }
        }
        $site = (string) ($r['website'] ?? '');
        $body .= '<tr' . ($archived ? ' class="opacity-50"' : '') . '>'
               . '<td><a href="/universities/' . $id . '">' . e((string) $r['name']) . '</a>'
               . ((int) ($r['is_sample'] ?? 0) === 1 ? ' <span class="badge text-bg-light border ms-1" title="Demo seed data">sample</span>' : '')
               . '</td>'
               . '<td><a href="/countries/' . (int) $r['country_id'] . '">' . e((string) $r['country_name']) . '</a></td>'
               . '<td>' . e(($r['city'] ?? '') ?: '—') . '</td>'
               . '<td>' . e(str_replace('_', ' ', (string) $r['type'])) . '</td>'
               . '<td>' . ($site !== '' ? '<a href="' . e($site) . '" target="_blank" rel="noopener noreferrer">Site ↗</a>' : '—') . '</td>'
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
  <?= View::pagination($page, $total, $per, '/universities', array_filter([
      'q' => $search,
      'status'     => $status !== 'active' ? $status : '',
      'country_id' => $countryId !== null ? (string) $countryId : '',
      'type'       => $type,
  ])) ?>
</div>
