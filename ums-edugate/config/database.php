<?php
/**
 * UMS EduGate — database connection abstraction (Phase 1)
 *
 * Provides a lazy singleton PDO connection built from the private
 * config file (config/config.local.php), falling back to the example
 * config only for local development. No credentials are hardcoded here.
 */

declare(strict_types=1);

final class Database
{
    private static ?PDO $pdo = null;
    private static ?array $config = null;

    /** Load configuration once: private local file preferred over example. */
    public static function config(): array
    {
        if (self::$config === null) {
            $example = require __DIR__ . '/config.example.php';
            $localFile = __DIR__ . '/config.local.php';
            if (is_readable($localFile)) {
                $local = require $localFile;
                if (is_array($local)) {
                    $example = array_replace_recursive($example, $local);
                }
            }
            self::$config = $example;
        }
        return self::$config;
    }

    /**
     * Return a shared PDO connection.
     * Throws RuntimeException with a clear, non-sensitive message on failure.
     */
    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $db = self::config()['database'];
        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $db['driver'],
            $db['host'],
            $db['port'],
            $db['name'],
            $db['charset']
        );

        try {
            self::$pdo = new PDO($dsn, $db['user'], $db['pass'], $db['options'] ?? [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (PDOException $e) {
            // Log detail locally, expose only a generic message to callers.
            error_log('[UMS EduGate] DB connection failed: ' . $e->getMessage());
            throw new RuntimeException(
                'Database connection failed. Check that MySQL/MariaDB is running and ' .
                'that config/config.local.php contains valid credentials.',
                0,
                $e
            );
        }

        return self::$pdo;
    }

    /** Test connectivity without requiring the schema to exist yet (Phase 1 helper). */
    public static function testConnection(): array
    {
        $db = self::config()['database'];
        $dsn = sprintf('%s:host=%s;port=%d;charset=%s', $db['driver'], $db['host'], $db['port'], $db['charset']);
        $started = microtime(true);
        try {
            $pdo = new PDO($dsn, $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $version = $pdo->query('SELECT VERSION()')->fetchColumn();
            return [
                'ok'      => true,
                'server'  => 'MySQL/MariaDB reachable',
                'version' => (string) $version,
                'ms'      => round((microtime(true) - $started) * 1000, 1),
            ];
        } catch (PDOException $e) {
            error_log('[UMS EduGate] DB test failed: ' . $e->getMessage());
            return [
                'ok'     => false,
                'server' => 'MySQL/MariaDB NOT reachable',
                'error'  => 'Connection failed (details logged to PHP error log; check config/config.local.php).',
            ];
        }
    }
}
