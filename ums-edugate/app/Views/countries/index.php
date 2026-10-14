<?php
/**
 * UMS EduGate — countries list view (Phase 5).
 * @var array $rows  @var int $total @var int $page @var int $per
 * @var string $search @var string $status @var bool $canEdit
 */
require_once BASE_PATH . '/app/Core/View.php';
require_once BASE_PATH . '/app/Core/Csrf.php';
?>
<?= View::pageHeading(
    'Countries',
    'Reference data for destination-country records. Archived countries can be restored; a country with active universities cannot be archived.',
    $canEdit ? '/countries/create' : '',
    'Add country'
) ?>

<form class="row g-2 align-items-end mb-3" method="get" action="/countries">
  <div class="col-12 col-md-5">
    <label class="form-label small text-secondary" for="q">Search</label>
    <input type="search" class="form-control form-control-sm" id="q" name="q"
           value="<?= e($search) ?>" placeholder="Name or region…">
  </div>
  <div class="col-6 col-md-3">
    <label class="form-label small text-secondary" for="status">Status</label>
    <select class="form-select form-select-sm" id="status" name="status">
      <?php foreach (['active' => 'Active', 'archived' => 'Archived', 'all' => 'All'] as $val => $lbl): ?>
        <option value="<?= e($val) ?>" <?= $status === $val ? 'selected' : '' ?>><?= e($lbl) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-2">
    <button class="btn btn-sm btn-primary w-100" type="submit">Filter</button>
  </div>
  <div class="col-12 col-md-2 text-md-end">
    <a class="btn btn-sm btn-outline-secondary w-100" href="/countries">Reset</a>
  </div>
</form>

<div class="card">
  <?php if ($rows === []): ?>
    <?= View::emptyState('No countries found', 'Adjust the search/status filters, or add the first country.', 'globe-americas') ?>
  <?php else: ?>
    <?php
    $head = '<tr><th scope="col">Country</th><th scope="col">ISO</th><th scope="col">Region</th>'
          . '<th scope="col">Currency</th><th scope="col">Universities</th><th scope="col">Confidence</th>'
          . '<th scope="col">Status</th>' . ($canEdit ? '<th scope="col" class="text-end">Actions</th>' : '') . '</tr>';
    $body = '';
    foreach ($rows as $r) {
        $iso2 = (string) ($r['iso2'] ?? '');
        $iso3 = (string) ($r['iso3'] ?? '');
        $iso  = trim($iso2 . ($iso3 !== '' ? ' / ' . $iso3 : ''));
        $archived = $r['archived_at'] !== null;
        $badgeVariant = match ((string) ($r['confidence_level'] ?? 'UNVERIFIED')) {
            'HIGH'   => 'success',
            'MEDIUM' => 'info',
            'LOW'    => 'warning',
            default  => 'secondary',
        };
        $actions = '';
        if ($canEdit) {
            $id = (int) $r['id'];
            if (!$archived) {
                $actions .= '<a class="btn btn-sm btn-outline-primary" href="/countries/' . $id . '/edit">Edit</a> ';
                $actions .= '<form method="post" action="/countries/' . $id . '/archive" class="d-inline" data-confirm="Archive this country?">'
                          . Csrf::field()
                          . '<button class="btn btn-sm btn-outline-danger" type="submit">Archive</button></form>';
            } else {
                $actions .= '<form method="post" action="/countries/' . $id . '/restore" class="d-inline">'
                          . Csrf::field()
                          . '<button class="btn btn-sm btn-outline-success" type="submit">Restore</button></form>';
            }
        }
        $body .= '<tr' . ($archived ? ' class="opacity-50"' : '') . '>'
               . '<td><a href="/countries/' . (int) $r['id'] . '">' . e((string) $r['name']) . '</a>'
               . ((int) ($r['is_sample'] ?? 0) === 1 ? ' <span class="badge text-bg-light border ms-1" title="Demo seed data">sample</span>' : '')
               . '</td>'
               . '<td class="text-nowrap">' . e($iso !== '' ? $iso : '—') . '</td>'
               . '<td>' . e(($r['region'] !== null && $r['region'] !== '') ? (string) $r['region'] : '—') . '</td>'
               . '<td>' . e(($r['currency_code'] !== null && $r['currency_code'] !== '') ? (string) $r['currency_code'] : '—') . '</td>'
               . '<td>' . (int) ($r['university_count'] ?? 0) . '</td>'
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
  <?= View::pagination($page, $total, $per, '/countries', array_filter(['q' => $search, 'status' => $status !== 'active' ? $status : ''])) ?>
</div>
