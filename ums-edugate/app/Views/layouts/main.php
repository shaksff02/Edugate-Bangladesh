<?php
/**
 * UMS EduGate — shared layout + tiny view renderer (Phase 1).
 * Bootstrap 5 via CDN for local dev; swap to a local copy for offline/XAMPP if needed.
 */

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function render_view(string $template, array $data = [], int $status = 200): void
{
    extract($data, EXTR_SKIP);
    $viewFile = __DIR__ . '/../' . $template . '.php';
    if (!is_readable($viewFile)) {
        throw new RuntimeException("View not found: {$template}");
    }
    if ($status !== 200 && !headers_sent()) {
        http_response_code($status);
    }
    ob_start();
    require $viewFile;
    $content = ob_get_clean();

    $pageTitle = $data['title'] ?? 'UMS EduGate';
    require __DIR__ . '/header.php';
    echo $content;
    require __DIR__ . '/footer.php';
}
