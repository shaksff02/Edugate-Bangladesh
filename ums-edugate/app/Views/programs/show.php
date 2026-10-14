<?php
/**
 * UMS EduGate — program detail view (Phase 7).
 * Related data tabs/placeholders per PRD: requirements arrive in Phase 8,
 * fees/scholarships in Phase 9, documents/visa in Phase 10.
 * @var array $program @var array $counts @var bool $canEdit
 */
require_once BASE_PATH . '/app/Core/View.php';
require_once BASE_PATH . '/app/Core/Csrf.php';
require_once BASE_PATH . '/app/Repositories/ProgramRepository.php';

$np = static fn($v): string => ($v === null || $v === '') ? 'Not provided' : e((string) $v);
$archived = $program['archived_at'] !== null;
$uniArchived = ($program['university_archived_at'] ?? null) !== null;
?>
<?= View::pageHeading(
    (string) $program['name'],
    $uniArchived ? 'This program belongs to an archived university; it is kept read-only for historical completeness.' : ''
) ?>

<?php if ($uniArchived): ?>
  <?= View::alert('The parent university of this program is archived.', 'warning', false) ?>
<?php endif; ?>

<div class="d-flex gap-2 mb-3 flex-wrap">
  <?php if ($canEdit && !$archived): ?>
    <a class="btn btn-sm btn-primary" href="/programs/<?= (int) $program['id'] ?>/edit">Edit program</a>
    <form method="post" action="/programs/<?= (int) $program['id'] ?>/archive" class="d-inline" data-confirm="Archive this program?">
      <?= Csrf::field() ?><button class="btn btn-sm btn-outline-danger" type="submit">Archive</button>
    </form>
  <?php elseif ($canEdit && $archived): ?>
    <form method="post" action="/programs/<?= (int) $program['id'] ?>/restore" class="d-inline">
      <?= Csrf::field() ?><button class="btn btn-sm btn-outline-success" type="submit">Restore</button>
    </form>
  <?php endif; ?>
  <a class="btn btn-sm btn-outline-secondary" href="/programs">Back to list</a>
</div>

<ul class="nav nav-tabs mb-3" role="tablist">
  <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button">Overview</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-requirements" type="button">Requirements <span class="badge text-bg-secondary"><?= (int) $counts['academic_requirements'] + (int) $counts['language_requirements'] ?></span></button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-fees" type="button">Fees &amp; scholarships <span class="badge text-bg-secondary"><?= (int) $counts['fees'] + (int) $counts['scholarships'] ?></span></button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-documents" type="button">Documents <span class="badge text-bg-secondary"><?= (int) $counts['document_requirements'] ?></span></button></li>
</ul>

<div class="tab-content">
  <div class="tab-pane fade show active" id="tab-overview">
    <div class="card">
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-sm-3">University</dt>
          <dd class="col-sm-9"><a href="/universities/<?= (int) $program['university_id'] ?>"><?= e((string) $program['university_name']) ?></a>
            <span class="text-secondary">(<?= e((string) ($program['country_name'] ?? '—')) ?>)</span></dd>
          <dt class="col-sm-3">Campus</dt>
          <dd class="col-sm-9"><?= $program['campus_id'] !== null
              ? '<a href="/campuses/' . (int) $program['campus_id'] . '">' . e((string) $program['campus_name']) . '</a>'
              : 'Not provided' ?></dd>
          <dt class="col-sm-3">Degree level</dt>
          <dd class="col-sm-9"><?= View::badge((string) $program['level'], 'info') ?></dd>
          <dt class="col-sm-3">Field / major</dt>
          <dd class="col-sm-9"><?= $np($program['field'] ?? null) ?></dd>
          <dt class="col-sm-3">Duration</dt>
          <dd class="col-sm-9"><?= $program['duration_months'] !== null ? (int) $program['duration_months'] . ' months' : 'Not provided' ?></dd>
          <dt class="col-sm-3">Credits</dt>
          <dd class="col-sm-9"><?= $program['credits'] !== null ? (int) $program['credits'] : 'Not provided' ?></dd>
          <dt class="col-sm-3">Language of instruction</dt>
          <dd class="col-sm-9"><?= $np($program['language_of_instruction'] ?? null) ?></dd>
          <dt class="col-sm-3">Study mode</dt>
          <dd class="col-sm-9"><?= e(ProgramRepository::MODES[(string) ($program['study_mode'] ?? 'ONCAMPUS')] ?? 'On-campus') ?></dd>
          <dt class="col-sm-3">Confidence</dt>
          <dd class="col-sm-9"><?= View::badge((string) ($program['confidence_level'] ?? 'UNVERIFIED'),
              match ((string) ($program['confidence_level'] ?? 'UNVERIFIED')) {
                  'HIGH' => 'success', 'MEDIUM' => 'info', 'LOW' => 'warning', default => 'secondary' }) ?></dd>
          <dt class="col-sm-3">Status</dt>
          <dd class="col-sm-9"><?= $archived ? View::badge('Archived', 'dark') : View::badge('Active', 'success') ?></dd>
          <dt class="col-sm-3">Description</dt>
          <dd class="col-sm-9"><?= $np($program['description'] ?? null) ?></dd>
        </dl>
      </div>
    </div>
  </div>

  <div class="tab-pane fade" id="tab-requirements">
    <div class="card"><div class="card-body">
      <?= View::emptyState(
          'Academic & language requirements coming in Phase 8',
          (int) $counts['academic_requirements'] + (int) $counts['language_requirements'] > 0
              ? (int) $counts['academic_requirements'] . ' academic and ' . (int) $counts['language_requirements'] . ' language rule(s) are stored; the management UI ships with Phase 8.'
              : 'No requirement records yet. The CRUD screens ship with Phase 8.',
          'clipboard-check'
      ) ?>
    </div></div>
  </div>

  <div class="tab-pane fade" id="tab-fees">
    <div class="card"><div class="card-body">
      <?= View::emptyState('Fees & scholarships coming in Phase 9', 'Cost and award management ships with Phase 9.', 'cash-stack') ?>
    </div></div>
  </div>

  <div class="tab-pane fade" id="tab-documents">
    <div class="card"><div class="card-body">
      <?= View::emptyState('Documents & visa info coming in Phase 10', 'Checklists and visa guidance ship with Phase 10.', 'file-earmark-text') ?>
    </div></div>
  </div>
</div>
