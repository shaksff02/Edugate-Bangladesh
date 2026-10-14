<?php
/**
 * UMS EduGate — country create/edit form (Phase 5).
 * @var array|null $country existing row (edit) or null (create)
 * @var array<string,string> $errors
 * @var array|null $old submitted input on validation failure
 * @var string $action form target, $heading page heading
 */
require_once BASE_PATH . '/app/Core/View.php';
require_once BASE_PATH . '/app/Core/Csrf.php';

$val = static function (string $key) use ($country, $old): string {
    if ($old !== null) {
        return (string) ($old[$key] ?? '');
    }
    return (string) ($country[$key] ?? '');
};
$fld = static function (string $name, string $label, array $extra = []) use ($val, $errors): array {
    return $extra + ['name' => $name, 'label' => $label, 'value' => $val($name), 'error' => $errors[$name] ?? null];
};
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
          <?= View::field($fld('name', 'Country name', ['required' => true, 'maxlength' => 120])) ?>
        </div>
        <div class="col-6 col-md-3">
          <?= View::field($fld('iso2', 'ISO-2 code', ['maxlength' => 2, 'placeholder' => 'BD', 'help' => '2 letters, e.g. BD'])) ?>
        </div>
        <div class="col-6 col-md-3">
          <?= View::field($fld('iso3', 'ISO-3 code', ['maxlength' => 3, 'placeholder' => 'BGD', 'help' => '3 letters, e.g. BGD'])) ?>
        </div>
        <div class="col-6 col-md-4">
          <?= View::field($fld('region', 'Region', ['maxlength' => 80, 'placeholder' => 'South Asia'])) ?>
        </div>
        <div class="col-6 col-md-4">
          <?= View::field($fld('currency_code', 'Currency code', ['maxlength' => 3, 'placeholder' => 'BDT'])) ?>
        </div>
        <div class="col-12 col-md-4">
          <?= View::select($fld('confidence_level', 'Confidence level'), [
              'UNVERIFIED' => 'Unverified', 'LOW' => 'Low', 'MEDIUM' => 'Medium', 'HIGH' => 'High',
          ]) ?>
        </div>
        <div class="col-12">
          <?= View::field($fld('visa_notes', 'Visa notes', ['type' => 'textarea', 'help' => 'Free-text notes for counsellors.']) ) ?>
        </div>
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Save country</button>
        <a class="btn btn-outline-secondary" href="/countries">Cancel</a>
      </div>
    </form>
  </div>
</div>
