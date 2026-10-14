<?php
/**
 * UMS EduGate — program create/edit form (Phase 7).
 * @var array|null $program @var array<string,string> $errors
 * @var array|null $old @var string $action @var string $heading
 * @var array $universities @var array $campuses
 */
require_once BASE_PATH . '/app/Core/View.php';
require_once BASE_PATH . '/app/Core/Csrf.php';
require_once BASE_PATH . '/app/Repositories/ProgramRepository.php';

$val = static function (string $key) use ($program, $old): string {
    if ($old !== null) {
        return (string) ($old[$key] ?? '');
    }
    return (string) ($program[$key] ?? '');
};
$fld = static function (string $name, string $label, array $extra = []) use ($val, $errors): array {
    return $extra + ['name' => $name, 'label' => $label, 'value' => $val($name), 'error' => $errors[$name] ?? null];
};
$uniOptions = [];
foreach ($universities as $u) {
    $uniOptions[(int) $u['id']] = (string) $u['name'];
}
$campusOptions = ['' => '— No specific campus —'];
foreach ($campuses as $cp) {
    $campusOptions[(int) $cp['id']] = (string) $cp['name'] . (($cp['city'] ?? '') ? ' (' . $cp['city'] . ')' : '');
}
$levelOptions = [];
foreach (ProgramRepository::LEVELS as $l) {
    $levelOptions[$l] = $l;
}
?>
<?= View::pageHeading((string) $heading, '') ?>

<div class="card">
  <div class="card-body">
    <?php if ($errors !== []): ?>
      <?= View::alert('Please fix the highlighted field(s).', 'danger', false) ?>
    <?php endif; ?>
    <form method="post" action="<?= e($action) ?>" novalidate>
      <?= Csrf::field() ?>
      <div class="row">
        <div class="col-12 col-md-6">
          <?= View::field($fld('name', 'Program name', ['required' => true, 'maxlength' => 190])) ?>
        </div>
        <div class="col-6 col-md-3">
          <?= View::select($fld('university_id', 'University', ['required' => true]), $uniOptions) ?>
        </div>
        <div class="col-6 col-md-3">
          <?= View::select($fld('campus_id', 'Campus'), $campusOptions) ?>
        </div>
        <div class="col-6 col-md-3">
          <?= View::select($fld('level', 'Degree level', ['required' => true]), $levelOptions) ?>
        </div>
        <div class="col-6 col-md-3">
          <?= View::field($fld('field', 'Field / major', ['maxlength' => 120])) ?>
        </div>
        <div class="col-6 col-md-2">
          <?= View::field($fld('duration_months', 'Duration (months)', ['type' => 'number', 'min' => 1, 'max' => 120])) ?>
        </div>
        <div class="col-6 col-md-2">
          <?= View::field($fld('credits', 'Credits', ['type' => 'number', 'min' => 1, 'max' => 999])) ?>
        </div>
        <div class="col-6 col-md-3">
          <?= View::field($fld('language_of_instruction', 'Language of instruction', ['maxlength' => 60, 'placeholder' => 'e.g. English'])) ?>
        </div>
        <div class="col-6 col-md-3">
          <?= View::select($fld('study_mode', 'Study mode', ['required' => true]), ProgramRepository::MODES) ?>
        </div>
        <div class="col-12">
          <?= View::field($fld('description', 'Description', ['type' => 'textarea', 'rows' => 3])) ?>
        </div>
        <div class="col-12 col-md-4">
          <?= View::select($fld('confidence_level', 'Confidence level'), [
              'UNVERIFIED' => 'Unverified', 'LOW' => 'Low', 'MEDIUM' => 'Medium', 'HIGH' => 'High',
          ]) ?>
        </div>
      </div>
      <p class="small text-secondary mb-3">Leave duration/credits/language blank when unknown — they display as “Not provided”, never as zero.</p>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Save program</button>
        <a class="btn btn-outline-secondary" href="/programs">Cancel</a>
      </div>
    </form>
  </div>
</div>
