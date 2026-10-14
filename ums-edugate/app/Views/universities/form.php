<?php
/**
 * UMS EduGate — university create/edit form (Phase 5).
 * @var array|null $university @var array<string,string> $errors
 * @var array|null $old @var string $action @var string $heading @var array $countries
 */
require_once BASE_PATH . '/app/Core/View.php';
require_once BASE_PATH . '/app/Core/Csrf.php';

$val = static function (string $key) use ($university, $old): string {
    if ($old !== null) {
        return (string) ($old[$key] ?? '');
    }
    return (string) ($university[$key] ?? '');
};
$fld = static function (string $name, string $label, array $extra = []) use ($val, $errors): array {
    return $extra + ['name' => $name, 'label' => $label, 'value' => $val($name), 'error' => $errors[$name] ?? null];
};
$countryOptions = [];
foreach ($countries as $c) {
    $countryOptions[(int) $c['id']] = (string) $c['name'];
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
          <?= View::field($fld('name', 'University name', ['required' => true, 'maxlength' => 190])) ?>
        </div>
        <div class="col-6 col-md-3">
          <?= View::select($fld('country_id', 'Country', ['required' => true]), $countryOptions) ?>
        </div>
        <div class="col-6 col-md-3">
          <?= View::select($fld('type', 'Type', ['required' => true]), [
              'PUBLIC' => 'Public', 'PRIVATE' => 'Private', 'GOVERNMENT_AIDED' => 'Government aided',
              'INTERNATIONAL_BRANCH' => 'International branch', 'OTHER' => 'Other',
          ]) ?>
        </div>
        <div class="col-6 col-md-4">
          <?= View::field($fld('city', 'City', ['maxlength' => 120])) ?>
        </div>
        <div class="col-12 col-md-8">
          <?= View::field($fld('website', 'Website', ['type' => 'url', 'placeholder' => 'https://…', 'help' => 'http(s) URL; scheme added automatically when omitted.'])) ?>
        </div>
        <div class="col-12 col-md-6">
          <?= View::field($fld('ranking_note', 'Ranking note', ['maxlength' => 255, 'placeholder' => 'e.g. QS 2025: #411']) ) ?>
        </div>
        <div class="col-12 col-md-6">
          <?= View::select($fld('confidence_level', 'Confidence level'), [
              'UNVERIFIED' => 'Unverified', 'LOW' => 'Low', 'MEDIUM' => 'Medium', 'HIGH' => 'High',
          ]) ?>
        </div>
        <div class="col-12">
          <?= View::field($fld('description', 'Description', ['type' => 'textarea', 'rows' => 4])) ?>
        </div>
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Save university</button>
        <a class="btn btn-outline-secondary" href="/universities">Cancel</a>
      </div>
    </form>
  </div>
</div>
