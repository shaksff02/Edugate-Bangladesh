<?php
/**
 * UMS EduGate — shared layout + view renderer (Phase 3: UI shell).
 * Bootstrap 5 via CDN for local dev; swap to a local copy for offline/XAMPP if needed.
 *
 * Two shells (PRD Phase 3):
 *   - auth shell   : centered card, no sidebar/topbar (login page)
 *   - app shell    : sidebar + topbar + breadcrumb + footer (all authenticated pages)
 * The shell is chosen automatically per template via LAYOUTS below.
 */

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Templates rendered inside the minimal auth shell instead of the app shell. */
const LAYOUT_AUTH_TEMPLATES = ['auth/login', 'errors/404', 'errors/csrf'];

function render_view(string $template, array $data = [], int $status = 200): void
{
    extract($data, EXTR_SKIP);
    $viewFile = BASE_PATH . '/app/Views/' . $template . '.php';
    if (!is_readable($viewFile)) {
        throw new RuntimeException("View not found: {$template}");
    }
    if ($status !== 200 && !headers_sent()) {
        http_response_code($status);
    }

    // Session must be active before the shell renders flash messages / user chip.
    require_once BASE_PATH . '/app/Core/Session.php';
    Session::start();

    ob_start();
    require $viewFile;
    $content = ob_get_clean();

    $pageTitle = $data['title'] ?? 'UMS EduGate';
    $shell = in_array($template, LAYOUT_AUTH_TEMPLATES, true) ? 'auth' : 'app';

    require __DIR__ . '/header.php';           // opens <html>..<main>
    echo $content;
    require __DIR__ . '/footer.php';           // closes main/footer/scripts

    // Flash messages were displayed on this response — consume them once.
    Session::clearFlashes();
}
