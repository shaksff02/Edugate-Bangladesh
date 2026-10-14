<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle ?? 'UMS EduGate') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-body-tertiary">
<nav class="navbar navbar-expand-lg bg-dark" data-bs-theme="dark">
  <div class="container">
    <a class="navbar-brand fw-semibold" href="/">UMS EduGate</a>
    <span class="badge text-bg-warning align-self-center">Phase 1 — Foundation</span>
    <div class="ms-auto small text-secondary">
      <?= e($_SERVER['SERVER_SOFTWARE'] ?? 'PHP built-in server') ?>
    </div>
  </div>
</nav>
<main class="container py-4">
