<?php
/**
 * UMS EduGate — intake create/edit form (Phase 6).
 * @var array|null $intake @var array<string,string> $errors
 * @var array|null $old @var string $action @var string $heading @var array $universities
 */
require_once BASE_PATH . '/app/Core/View.php';
require_once BASE_PATH . '/app/Core/Csrf.php';

$val = static function (string $key) use ($intake, $old): string {
    if ($old !== null) {
        return (string) ($old[$key] ?? '');
    }
    return (string) ($intake[$key] ?? '');
};
$fld = static function (string $name, string $label, array $extra = []) use ($val, $errors): array {
    return $extra + ['name' => $name, 'label' => $label, 'value' => $val($name), 'error' => $errors[$name] ?? null];
};
$uniOptions = [];
foreach ($universities as $u) {
    $uniOptions[(int) $u['id']] = (string) $u['name'];
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
        <div class="col-12 col-md-5">
          <?= View::field($fld('name', 'Intake name', ['required' => true, 'maxlength' => 120, 'placeholder' => 'e.g. Fall'])) ?>
        </div>
        <div class="col-6 col-md-4">
          <?= View::select($fld('university_id', 'University', ['required' => true]), $uniOptions) ?>
        </div>
        <div class="col-6 col-md-3">
          <?= View::field($fld('intake_year', 'Intake year', ['type' => 'number', 'required' => true, 'min' => 2000, 'max' => 2100])) ?>
        </div>
        <div class="col-6 col-md-3">
          <?= View::field($fld('starts_on', 'Starts on', ['type' => 'date'])) ?>
        </div>
        <div class="col-6 col-md-3">
          <?= View::field($fld('ends_on', 'Ends on', ['type' => 'date', 'help' => 'Cannot be before the start date.'])) ?>
        </div>
        <div class="col-12 col-md-6">
          <?= View::select($fld('confidence_level', 'Confidence level'), [
              'UNVERIFIED' => 'Unverified', 'LOW' => 'Low', 'MEDIUM' => 'Medium', 'HIGH' => 'High',
          ]) ?>
        </div>
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Save intake</button>
        <a class="btn btn-outline-secondary" href="/intakes">Cancel</a>
      </div>
    </form>
  </div>
</div>
