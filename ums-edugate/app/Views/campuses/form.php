<?php
/**
 * UMS EduGate — campus create/edit form (Phase 6).
 * @var array|null $campus @var array<string,string> $errors
 * @var array|null $old @var string $action @var string $heading @var array $universities
 */
require_once BASE_PATH . '/app/Core/View.php';
require_once BASE_PATH . '/app/Core/Csrf.php';

$val = static function (string $key) use ($campus, $old): string {
    if ($old !== null) {
        return (string) ($old[$key] ?? '');
    }
    return (string) ($campus[$key] ?? '');
};
$fld = static function (string $name, string $label, array $extra = []) use ($val, $errors): array {
    return $extra + ['name' => $name, 'label' => $label, 'value' => $val($name), 'error' => $errors[$name] ?? null];
};
$uniOptions = [];
foreach ($universities as $u) {
    $uniOptions[(int) $u['id']] = (string) $u['name'];
}
$isMain = $old !== null ? (int) ($old['is_main'] ?? 0) : (int) ($campus['is_main'] ?? 0);
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
          <?= View::field($fld('name', 'Campus name', ['required' => true, 'maxlength' => 190])) ?>
        </div>
        <div class="col-6 col-md-6">
          <?= View::select($fld('university_id', 'University', ['required' => true]), $uniOptions) ?>
        </div>
        <div class="col-6 col-md-3">
          <?= View::field($fld('city', 'City', ['maxlength' => 120])) ?>
        </div>
        <div class="col-12 col-md-9">
          <?= View::field($fld('address', 'Address', ['type' => 'textarea', 'rows' => 2])) ?>
        </div>
        <div class="col-6 col-md-4">
          <div class="form-check mt-4">
            <input class="form-check-input" type="checkbox" value="1" id="is_main" name="is_main" <?= $isMain === 1 ? 'checked' : '' ?>>
            <label class="form-check-label" for="is_main">Main campus of the university</label>
          </div>
        </div>
        <div class="col-12 col-md-8">
          <?= View::select($fld('confidence_level', 'Confidence level'), [
              'UNVERIFIED' => 'Unverified', 'LOW' => 'Low', 'MEDIUM' => 'Medium', 'HIGH' => 'High',
          ]) ?>
        </div>
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Save campus</button>
        <a class="btn btn-outline-secondary" href="/campuses">Cancel</a>
      </div>
    </form>
  </div>
</div>
