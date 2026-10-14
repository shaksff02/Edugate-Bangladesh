<?php
/**
 * UMS EduGate — session management (Phase 3)
 *
 * Thin wrapper enforcing secure session settings before session_start():
 * cookie-only IDs, HttpOnly, SameSite=Lax, strict mode. Also provides
 * ID rotation on privilege change (login/logout) to prevent fixation.
 */

declare(strict_types=1);

final class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }
        $cfg = Database::config()['auth'] ?? [];
        session_name($cfg['session_name'] ?? 'UMSEDU_SESSID');
        session_set_cookie_params([
            'lifetime' => 0,                       // browser session cookie
            'path'     => '/',
            'domain'   => '',
            'secure'   => !empty($cfg['session_secure_cookie']), // HTTPS in prod
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_start();
        self::$started = true;
    }

    /** Rotate the session ID (call after login/logout). */
    public static function regenerate(): void
    {
        if (self::$started) {
            session_regenerate_id(true);
        }
    }

    public static function flash(string $key, ?string $message = null): ?string
    {
        if ($message !== null) {           // set
            $_SESSION['_flash'][$key] = $message;
            return null;
        }
        $val = $_SESSION['_flash'][$key] ?? null;  // get-and-clear
        unset($_SESSION['_flash'][$key]);
        return $val;
    }

    /** Peek at all pending flash messages without clearing them. */
    public static function peekFlashes(): array
    {
        return $_SESSION['_flash'] ?? [];
    }

    /** Clear all pending flash messages (called once after rendering). */
    public static function clearFlashes(): void
    {
        unset($_SESSION['_flash']);
    }

    public static function destroy(): void
    {
        if (self::$started && session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $p = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
            }
            session_destroy();
        }
        self::$started = false;
    }
}
