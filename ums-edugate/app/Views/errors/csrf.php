<?php
/**
 * UMS EduGate — CSRF failure page (Phase 3)
 *
 * Rendered when a submitted form token is missing or stale. The user
 * only needs to reload the form and try again; no sensitive detail is
 * exposed here.
 */
?>
<div class="d-flex flex-column align-items-center justify-content-center" style="min-height:60vh">
    <h1 class="h3 mb-2">Session expired</h1>
    <p class="text-muted mb-4">Your form session timed out or the security check failed. Please try again.</p>
    <a class="btn btn-primary" href="<?= BASE_URL ?>/login">Back to login</a>
</div>
