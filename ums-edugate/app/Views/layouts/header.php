<?php
/**
 * UMS EduGate — layout header (Phase 3 UI shell).
 * Renders either the minimal auth shell or the full app shell
 * (sidebar + topbar + breadcrumb), depending on $shell.
 * Requires: e(), View, Session; variables $pageTitle, $shell, optional $breadcrumbs, $path.
 */
require_once BASE_PATH . '/app/Core/View.php';

$path = $path ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$nav  = View::navigation();

/** Render one sidebar nav group list (shared by desktop + offcanvas). */
function umg_render_nav(array $nav, string $path): void
{
    foreach ($nav as $group => $items) {
        echo '<div class="small text-uppercase text-secondary px-3 pt-3 pb-1 fw-semibold">' . e((string) $group) . '</div>';
        foreach ($items as $item) {
            if ($item['state'] === 'hidden') {
                continue;
            }
            $active = $item['state'] === 'ready' && View::is_active($item['url'], rtrim($path, '/') ?: '/');
            if ($item['state'] === 'coming_soon') {
                echo '<span class="umg-nav-link disabled text-secondary" title="Coming in a later phase">'
                   . '<i class="bi bi-' . e($item['icon']) . ' me-2" aria-hidden="true"></i>' . e($item['label'])
                   . '<span class="badge text-bg-secondary ms-auto fw-normal">Soon</span></span>';
            } else {
                echo '<a class="umg-nav-link' . ($active ? ' active' : '') . '" href="' . e($item['url']) . '"'
                   . ($active ? ' aria-current="page"' : '') . '>'
                   . '<i class="bi bi-' . e($item['icon']) . ' me-2" aria-hidden="true"></i>' . e($item['label']) . '</a>';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle ?? 'UMS EduGate') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/assets/css/app.css" rel="stylesheet">
</head>
<?php if (($shell ?? 'app') === 'auth'): ?>
<body class="bg-body-tertiary umg-auth-shell">
<main class="container py-4">
  <div class="text-center mb-2">
    <a class="navbar-brand fw-semibold text-decoration-none" href="/">
      <i class="bi bi-mortarboard-fill me-1" aria-hidden="true"></i>UMS EduGate
    </a>
  </div>
<?php else: ?>
<body class="bg-body-tertiary umg-app-shell">
<a class="visually-hidden-focusable" href="#umg-main">Skip to main content</a>

<!-- Offcanvas sidebar for mobile/tablet -->
<div class="offcanvas offcanvas-start umg-sidebar bg-dark text-light" tabindex="-1" id="umgSidebar" aria-label="Main navigation" data-bs-theme="dark">
  <div class="offcanvas-header border-bottom border-secondary">
    <h5 class="offcanvas-title"><i class="bi bi-mortarboard-fill me-1" aria-hidden="true"></i>UMS EduGate</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body p-0">
    <nav class="umg-nav flex-column p-2"><?php umg_render_nav($nav, $path); ?></nav>
  </div>
</div>

<div class="umg-layout">
  <!-- Desktop sidebar -->
  <aside class="umg-sidebar-desktop d-none d-lg-flex flex-column bg-dark text-light" aria-label="Main navigation">
    <div class="px-3 py-3 border-bottom border-secondary fw-semibold">
      <a class="text-decoration-none text-light" href="/">
        <i class="bi bi-mortarboard-fill me-1" aria-hidden="true"></i>UMS EduGate
      </a>
    </div>
    <nav class="umg-nav flex-column p-2 overflow-auto flex-grow-1"><?php umg_render_nav($nav, $path); ?></nav>
  </aside>

  <div class="umg-content-col d-flex flex-column min-vh-100">
    <!-- Topbar -->
    <header class="navbar navbar-expand sticky-top bg-body shadow-sm umg-topbar">
      <div class="container-fluid px-3">
        <button class="btn btn-outline-secondary btn-sm d-lg-none" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#umgSidebar" aria-controls="umgSidebar" aria-label="Open menu">
          <i class="bi bi-list" aria-hidden="true"></i>
        </button>
        <span class="navbar-text small text-secondary d-none d-md-inline">University Management System · Education Consultancy CRM</span>
        <div class="ms-auto d-flex align-items-center gap-2">
          <?php if (class_exists('Auth') && Auth::check()): ?>
            <span class="badge text-bg-light border"><?= e((string) Auth::role()) ?></span>
            <div class="dropdown">
              <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle me-1" aria-hidden="true"></i><?= e((string) Auth::name()) ?>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="/dashboard"><i class="bi bi-speedometer2 me-2" aria-hidden="true"></i>Dashboard</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <form method="post" action="/logout" class="m-0">
                    <?= Csrf::field() ?>
                    <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Sign out</button>
                  </form>
                </li>
              </ul>
            </div>
          <?php else: ?>
            <a class="btn btn-sm btn-primary" href="/login">Sign in</a>
          <?php endif; ?>
        </div>
      </div>
    </header>

    <main id="umg-main" class="flex-grow-1 px-3 px-lg-4 py-4">
      <div class="container-fluid" style="max-width: 1200px;">
        <?php if (!empty($breadcrumbs)): ?>
          <?= View::breadcrumb($breadcrumbs) ?>
        <?php endif; ?>
        <?= View::flashes() ?>
<?php endif; ?>
