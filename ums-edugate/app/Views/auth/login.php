<?php
/** UMS EduGate — login form (Phase 3). All dynamic output escaped with e(). */
?>
<div class="row justify-content-center">
  <div class="col-md-5 col-lg-4">
    <div class="card shadow-sm mt-5">
      <div class="card-body p-4">
        <h1 class="h4 mb-3 text-center">Sign in</h1>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger py-2" role="alert"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
          <div class="alert alert-success py-2" role="alert"><?= e($success) ?></div>
        <?php endif; ?>

        <form method="post" action="/login" autocomplete="off">
          <?= Csrf::field() ?>
          <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input class="form-control" type="email" id="email" name="email"
                   required maxlength="190" value="<?= e($old ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <input class="form-control" type="password" id="password" name="password"
                   required maxlength="200" autocomplete="current-password">
          </div>
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
            <label class="form-check-label" for="remember">Keep me signed in (30 days)</label>
          </div>
          <button class="btn btn-primary w-100" type="submit">Sign in</button>
        </form>

        <p class="text-muted small mt-3 mb-0">
          Demo accounts are seeded locally (see README). Passwords are verified
          against <code>password_hash()</code> values only — never plaintext.
        </p>
      </div>
    </div>
  </div>
</div>
