<?php
/**
 * UMS EduGate — CSRF protection (Phase 3)
 *
 * Synchronizer-token pattern: a per-session random token, embedded as a
 * hidden field in every state-changing form and verified on POST. The
 * comparison is constant-time. Tokens live only server-side (session).
 */

declare(strict_types=1);

final class Csrf
{
    private const SESSION_KEY = 'csrf_token';

    /** Return the current session token, creating one if needed. */
    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    /** Hidden input to drop inside every <form method="post">. */
    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    /**
     * Validate the request. Aborts with 419 (deliberate error page, never
     * blank) when the submitted token is missing or mismatched.
     */
    public static function check(): void
    {
        $sent = $_POST['_csrf'] ?? '';
        $known = $_SESSION[self::SESSION_KEY] ?? '';
        if (!is_string($sent) || $known === '' || !hash_equals($known, $sent)) {
            Session::destroy(); // possible replay/session fixation — start clean
            http_response_code(419);
            render_view('errors/csrf', ['title' => 'Session expired']);
            exit;
        }
    }
}
