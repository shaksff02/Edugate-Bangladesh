<?php // Internal demo of every reusable component (used by tests + design reference). ?>
<?= View::pageHeading('UI components', 'Reusable Bootstrap building blocks used by all modules.', '/dashboard', 'Back to dashboard') ?>

<div class="card shadow-sm mb-3">
  <div class="card-header fw-semibold">Alerts</div>
  <div class="card-body">
    <?= View::alert('Operation completed successfully.', 'success') ?>
    <?= View::alert('Something went wrong — please retry.', 'danger') ?>
  </div>
</div>

<div class="card shadow-sm mb-3">
  <div class="card-header fw-semibold">Badges</div>
  <div class="card-body d-flex gap-2 flex-wrap">
    <?= View::badge('Active', 'success') ?><?= View::badge('Pending', 'warning') ?><?= View::badge('Closed', 'dark') ?>
  </div>
</div>

<div class="card shadow-sm mb-3">
  <div class="card-header fw-semibold">Table + pagination + empty state</div>
  <div class="card-body">
    <?= View::table('<tr><th>Name</th><th>Status</th></tr>', '<tr><td>Example University</td><td>' . View::badge('Active','success') . '</td></tr>') ?>
    <div class="mt-3"><?= View::pagination(2, 95, 20, '/components') ?></div>
    <hr>
    <?= View::emptyState('No records found.', 'Try adjusting your filters or add the first record.', 'search') ?>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-header fw-semibold">Form fields</div>
  <div class="card-body">
    <?= View::field(['label' => 'Country name', 'name' => 'country_name', 'value' => 'Bangladesh', 'required' => true, 'maxlength' => 100]) ?>
    <?= View::field(['label' => 'Contact email', 'name' => 'email', 'type' => 'email', 'error' => 'Enter a valid email address.']) ?>
    <?= View::select(['label' => 'Status', 'name' => 'status', 'value' => 'active'], ['active' => 'Active', 'inactive' => 'Inactive']) ?>
  </div>
</div>
