<?php
/**
 * UMS EduGate — application-round create/edit form (Phase 6).
 * @var array|null $round @var array<string,string> $errors
 * @var array|null $old @var string $action @var string $heading @var array $universities @var array $intakes
 */
require_once BASE_PATH . '/app/Core/View.php';
require_once BASE_PATH . '/app/Core/Csrf.php';

$val = static function (string $key) use ($round, $old): string {
    if ($old !== null) {
        return (string) ($old[$key] ?? '');
    }
    // DB datetimes "Y-m-d H:i:s" -> datetime-local input "Y-m-d\TH:i"
    $v = (string) ($round[$key] ?? '');
    if (in_array($key, ['opens_at', 'closes_at'], true) && $v !== '') {
        $v = str_replace(' ', 'T', substr($v, 0, 16));
    }
    return $v;
};
$fld = static function (string $name, string $label, array $extra = []) use ($val, $errors): array {
    return $extra + ['name' => $name, 'label' => $label, 'value' => $val($name), 'error' => $errors[$name] ?? null];
};
$intakeOptions = [];
foreach ($intakes as $i) {
    $intakeOptions[(int) $i['id']] = (string) $i['name'] . ' ' . (int) $i['intake_year'] . ' — ' . (string) $i['university_name']
        ?? '';
}
// university_name is not in activeIntakes columns; rebuild label safely.
$uniById = [];
foreach ($universities as $u) { $uniById[(int) $u['id']] = (string) $u['name']; }
$intakeOptions = [];
foreach ($intakes as $i) {
    $uniName = $uniById[(int) $i['university_id']] ?? '?';
    $intakeOptions[(int) $i['id']] = (string) $i['name'] . ' ' . (int) $i['intake_year'] . ' — ' . $uniName;
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
          <?= View::field($fld('name', 'Round name', ['required' => true, 'maxlength' => 120, 'placeholder' => 'e.g. Round 1'])) ?>
        </div>
        <div class="col-12 col-md-7">
          <?= View::select($fld('intake_id', 'Intake', ['required' => true]), $intakeOptions) ?>
        </div>
        <div class="col-6 col-md-4">
          <?= View::field($fld('opens_at', 'Opens at', ['type' => 'datetime-local'])) ?>
        </div>
        <div class="col-6 col-md-4">
          <?= View::field($fld('closes_at', 'Closes at', ['type' => 'datetime-local', 'help' => 'Cannot be before opening. Left empty → deadline stays Unknown.'])) ?>
        </div>
        <div class="col-6 col-md-4">
          <?= View::field($fld('result_date', 'Result date', ['type' => 'date'])) ?>
        </div>
        <div class="col-12 col-md-6">
          <?= View::select($fld('confidence_level', 'Confidence level'), [
              'UNVERIFIED' => 'Unverified', 'LOW' => 'Low', 'MEDIUM' => 'Medium', 'HIGH' => 'High',
          ]) ?>
        </div>
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Save round</button>
        <a class="btn btn-outline-secondary" href="/rounds">Cancel</a>
      </div>
    </form>
  </div>
</div>
