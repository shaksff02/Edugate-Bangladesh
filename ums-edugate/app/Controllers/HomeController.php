<?php
/**
 * UMS EduGate — Home controller (Phase 1: project foundation only).
 * Shows environment status. No business features are implemented yet.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

final class HomeController
{
    public function index(): void
    {
        $config = Database::config();
        $dbTest = Database::testConnection();
        render_view('home/index', [
            'title'  => $config['app']['name'] . ' — Foundation',
            'config' => $config,
            'dbTest' => $dbTest,
        ]);
    }

    public function health(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(200);
        echo json_encode([
            'php'      => PHP_VERSION,
            'pdo_mysql'=> extension_loaded('pdo_mysql'),
            'database' => Database::testConnection(),
        ], JSON_PRETTY_PRINT);
    }

    public function notFound(): void
    {
        self::render404();
    }

    public static function render404(): void
    {
        http_response_code(404);
        render_view('errors/404', ['title' => 'Page not found']);
    }
}
