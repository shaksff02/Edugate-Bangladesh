<?php
/**
 * UMS EduGate — deadline report (Phase 6 PRD acceptance).
 * @var array $report ['upcoming'=>[], 'closing_soon'=>[], 'passed'=>[], 'unknown'=>[]]
 */
require_once BASE_PATH . '/app/Core/View.php';
?>
<?= View::pageHeading('Deadline report', 'Active application rounds grouped by closing deadline. Missing deadlines are shown as Unknown — never guessed.') ?>

<?php
$sections = [
    ['key' => 'closing_soon', 'title' => 'Closing soon (≤ 14 days)', 'variant' => 'warning', 'icon' => 'alarm'],
    ['key' => 'upcoming',     'title' => 'Upcoming',                 'variant' => 'success', 'icon' => 'calendar-check'],
    ['key' => 'passed',       'title' => 'Passed',                   'variant' => 'secondary', 'icon' => 'history'],
    ['key' => 'unknown',      'title' => 'Unknown deadline',         'variant' => 'light text-body-secondary border', 'icon' => 'question-circle'],
];
foreach ($sections as $s):
    $items = $report[$s['key']] ?? [];
?>
<div class="card mb-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="fw-semibold"><?= e($s['title']) ?></span>
    <?= View::badge((string) count($items), $s['variant']) ?>
  </div>
  <?php if ($items === []): ?>
    <div class="card-body"><span class="small text-secondary">Nothing here.</span></div>
  <?php else: ?>
    <?php
    $head = '<tr><th>Round</th><th>Intake</th><th>University</th><th>Closes at</th><th>Days</th></tr>';
    $body = '';
    foreach ($items as $r) {
        $dd = $r['deadline'];
        $days = $dd['days'] === null ? '—' : (string) abs((int) $dd['days']);
        $body .= '<tr>'
               . '<td><a href="/rounds/' . (int) $r['id'] . '">' . e((string) $r['name']) . '</a></td>'
               . '<td class="small">' . e((string) $r['intake_name'] . ' ' . (int) $r['intake_year']) . '</td>'
               . '<td class="small">' . e((string) $r['university_name']) . '</td>'
               . '<td class="small">' . e(($r['closes_at'] ?? '') ?: '—') . '</td>'
               . '<td>' . $days . '</td>'
               . '</tr>';
    }
    echo View::table($head, $body);
    ?>
  <?php endif; ?>
</div>
<?php endforeach; ?>
