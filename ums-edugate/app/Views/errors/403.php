<?php
/**
 * UMS EduGate — 403 error page (rendered by Auth::require on RBAC denial).
 * @var string $title @var string $message
 */
require_once BASE_PATH . '/app/Core/View.php';
?>
<?= View::pageHeading('Access denied', '') ?>
<div class="card">
  <div class="card-body">
    <?= View::errorState((string) ($message ?? 'You do not have permission to perform this action.'), '/dashboard') ?>
  </div>
</div>
