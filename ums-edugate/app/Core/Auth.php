<?php
/**
 * UMS EduGate — Auth core (Phase 3)
 *
 * Auth      : session login state, secure "remember me" tokens
 *             (selector + hashed validator, single-use rotation), and
 *             audit-log writes.
 * Throttle  : per-account lockout (users.failed_attempts/locked_until)
 *             plus per-IP failed-attempt window from login_attempts.
 * Rbac      : ADMIN / EDITOR / READ_ONLY permission matrix (PRD §4).
 *
 * Passwords are verified only via password_verify() against the
 * password_hash() values stored by migration/seed — never plaintext.
 */

declare(strict_types=1);

/* --------------------------------------------------------------------- */
final class Throttle
{
    public const MAX_ATTEMPTS = 5;          // failures before temporary lock
    public const LOCK_MINUTES = 15;         // account lock duration
    public const IP_WINDOW_MINUTES = 15;    // sliding window for IP throttle
    public const IP_MAX_ATTEMPTS = 20;      // failures per window per IP

    /** True when this email/IP pair must be refused right now. */
    public static function blocked(PDO $pdo, string $email, ?string $ip): bool
    {
        return self::accountLocked($pdo, $email) || self::ipBlocked($pdo, $ip);
    }

    public static function accountLocked(PDO $pdo, string $email): bool
    {
        $st = $pdo->prepare('SELECT locked_until FROM users WHERE email = ?');
        $st->execute([$email]);
        $until = $st->fetchColumn();
        return $until !== null && $until !== false && strtotime((string) $until) > time();
    }

    public static function ipBlocked(PDO $pdo, ?string $ip): bool
    {
        if ($ip === null || $ip === '') {
            return false;
        }
        $st = $pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE ip_address = ? AND success = 0
               AND created_at > (NOW() - INTERVAL ' . self::IP_WINDOW_MINUTES . ' MINUTE)'
        );
        $st->execute([$ip]);
        return (int) $st->fetchColumn() >= self::IP_MAX_ATTEMPTS;
    }

    /** Record an attempt and apply/clear the per-account lockout. */
    public static function record(PDO $pdo, string $email, ?string $ip, bool $success, ?string $userAgent): void
    {
        $st = $pdo->prepare(
            'INSERT INTO login_attempts (email_attempted, ip_address, user_agent, success)
             VALUES (?, ?, ?, ?)'
        );
        $st->execute([mb_substr($email, 0, 190), $ip, mb_substr((string) $userAgent, 0, 255), $success ? 1 : 0]);

        if ($success) {
            $pdo->prepare('UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE email = ?')
                ->execute([$email]);
            return;
        }

        $pdo->prepare('UPDATE users SET failed_attempts = failed_attempts + 1 WHERE email = ?')
            ->execute([$email]);
        $st = $pdo->prepare('SELECT failed_attempts FROM users WHERE email = ?');
        $st->execute([$email]);
        $fails = (int) $st->fetchColumn();
        if ($fails >= self::MAX_ATTEMPTS) {
            $pdo->prepare(
                'UPDATE users SET locked_until = NOW() + INTERVAL ' . self::LOCK_MINUTES . ' MINUTE WHERE email = ?'
            )->execute([$email]);
        }
    }

    /** Minutes remaining on an account lock (0 when not locked). */
    public static function lockMinutesLeft(PDO $pdo, string $email): int
    {
        $st = $pdo->prepare('SELECT locked_until FROM users WHERE email = ?');
        $st->execute([$email]);
        $until = $st->fetchColumn();
        if (!$until) {
            return 0;
        }
        return max(0, (int) ceil((strtotime((string) $until) - time()) / 60));
    }
}

/* --------------------------------------------------------------------- */
final class Audit
{
    public static function log(?int $actorUserId, string $action, string $entityType = 'auth', ?int $entityId = null): void
    {
        try {
            $st = Database::connection()->prepare(
                'INSERT INTO audit_logs (actor_user_id, action, entity_type, entity_id, ip_address)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $st->execute([$actorUserId, $action, $entityType, $entityId, $_SERVER['REMOTE_ADDR'] ?? null]);
        } catch (Throwable $e) {
            // Audit failure must never break login; keep a server-side trace.
            error_log('[UMS EduGate] audit write failed: ' . $e->getMessage());
        }
    }
}

/* --------------------------------------------------------------------- */
final class Auth
{
    private const UID_KEY = 'user_id';
    private const ROLE_KEY = 'user_role';
    private const NAME_KEY = 'user_name';

    public static function pdo(): PDO
    {
        return Database::connection();
    }

    public static function check(): bool
    {
        return isset($_SESSION[self::UID_KEY]);
    }

    public static function id(): ?int
    {
        return isset($_SESSION[self::UID_KEY]) ? (int) $_SESSION[self::UID_KEY] : null;
    }

    public static function role(): ?string
    {
        return $_SESSION[self::ROLE_KEY] ?? null;
    }

    public static function name(): ?string
    {
        return $_SESSION[self::NAME_KEY] ?? null;
    }

    /** Attempt a credential login. Returns ['ok'=>bool,'message'=>string]. */
    public static function attempt(string $email, string $password, bool $remember): array
    {
        $pdo = self::pdo();
        $email = strtolower(trim($email));
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        if ($email === '' || $password === '' || strlen($password) > 200) {
            return ['ok' => false, 'message' => 'Enter your email and password.'];
        }

        if (Throttle::blocked($pdo, $email, $ip)) {
            Audit::log(null, 'login_blocked_throttle', 'auth', null);
            return ['ok' => false, 'message' => 'Too many attempts. Please wait before trying again.'];
        }

        $st = $pdo->prepare(
            'SELECT id, name, email, password_hash, role, is_active FROM users WHERE email = ?'
        );
        $st->execute([$email]);
        $user = $st->fetch();

        // Uniform failure message: never reveal whether the email exists.
        $invalid = ['ok' => false, 'message' => 'Invalid email or password.'];

        if (!$user || !(int) $user['is_active']) {
            Throttle::record($pdo, $email, $ip, false, $_SERVER['HTTP_USER_AGENT'] ?? null);
            Audit::log(null, 'login_failed_unknown_or_disabled', 'auth', null);
            return $invalid;
        }

        if (!password_verify($password, (string) $user['password_hash'])) {
            Throttle::record($pdo, $email, $ip, false, $_SERVER['HTTP_USER_AGENT'] ?? null);
            Audit::log((int) $user['id'], 'login_failed_bad_password', 'auth', (int) $user['id']);
            return $invalid;
        }

        // Success: transparent rehash if algorithm/cost changed.
        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }

        Throttle::record($pdo, $email, $ip, true, $_SERVER['HTTP_USER_AGENT'] ?? null);
        self::loginAs($user);
        $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);

        if ($remember) {
            self::issueRememberToken((int) $user['id']);
        }

        Audit::log((int) $user['id'], 'login_success', 'auth', (int) $user['id']);
        return ['ok' => true, 'message' => 'Welcome back, ' . $user['name'] . '.'];
    }

    /** Populate session from a DB row (regenerates the session ID first). */
    private static function loginAs(array $user): void
    {
        Session::regenerate();
        $_SESSION[self::UID_KEY] = (int) $user['id'];
        $_SESSION[self::ROLE_KEY] = (string) $user['role'];
        $_SESSION[self::NAME_KEY] = (string) $user['name'];
    }

    /** Log out: revoke all remember tokens, destroy session. */
    public static function logout(): void
    {
        if (self::check()) {
            $st = self::pdo()->prepare('DELETE FROM remember_tokens WHERE user_id = ?');
            $st->execute([self::id()]);
            Audit::log(self::id(), 'logout', 'auth', self::id());
        }
        self::clearRememberCookie();
        Session::destroy();
    }

    /* ---------------- remember me (secure, rotated tokens) ------------- */

    private static function cookieName(): string
    {
        return (Database::config()['auth']['remember_cookie'] ?? 'UMSEDU_REMEMBER');
    }

    private static function issueRememberToken(int $userId): void
    {
        $selector  = bin2hex(random_bytes(8));            // 16 hex chars
        $validator = bin2hex(random_bytes(32));           // secret half
        $days = (int) (Database::config()['auth']['remember_days'] ?? 30);

        $st = self::pdo()->prepare(
            'INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at)
             VALUES (?, ?, ?, NOW() + INTERVAL ? DAY)'
        );
        $st->execute([$userId, $selector, hash('sha256', $validator), $days]);

        setcookie(self::cookieName(), $selector . ':' . $validator, [
            'expires'  => time() + $days * 86400,
            'path'     => '/',
            'secure'   => !empty(Database::config()['auth']['session_secure_cookie']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function clearRememberCookie(): void
    {
        if (!headers_sent()) {
            setcookie(self::cookieName(), '', [
                'expires' => time() - 42000, 'path' => '/',
                'httponly' => true, 'samesite' => 'Lax',
            ]);
        }
    }

    /** If no session but a valid remember cookie exists, restore login. */
    public static function resumeFromCookie(): void
    {
        $raw = $_COOKIE[self::cookieName()] ?? '';
        if ($raw === '' || self::check() || substr_count($raw, ':') !== 1) {
            return;
        }
        [$selector, $validator] = explode(':', $raw, 2);
        if ($selector === '' || $validator === '') {
            return;
        }

        $pdo = self::pdo();
        $st = $pdo->prepare(
            'SELECT r.id AS token_id, r.user_id, r.validator_hash, r.expires_at,
                    u.name, u.role, u.is_active
             FROM remember_tokens r JOIN users u ON u.id = r.user_id
             WHERE r.selector = ?'
        );
        $st->execute([$selector]);
        $row = $st->fetch();

        if (!$row) {                       // unknown selector → stale cookie
            self::clearRememberCookie();
            return;
        }
        if (!hash_equals((string) $row['validator_hash'], hash('sha256', $validator))) {
            // Possible token theft/replay: revoke every token for this user.
            $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = ?')->execute([(int) $row['user_id']]);
            Audit::log((int) $row['user_id'], 'remember_token_mismatch_revoked', 'auth', (int) $row['user_id']);
            self::clearRememberCookie();
            return;
        }
        if (strtotime((string) $row['expires_at']) <= time() || !(int) $row['is_active']) {
            $pdo->prepare('DELETE FROM remember_tokens WHERE id = ?')->execute([(int) $row['token_id']]);
            self::clearRememberCookie();
            return;
        }

        // Single-use rotation: replace the consumed token with a fresh one.
        $userSt = $pdo->prepare('SELECT id, name, email, role FROM users WHERE id = ?');
        $userSt->execute([(int) $row['user_id']]);
        $user = $userSt->fetch();
        if (!$user) {
            self::clearRememberCookie();
            return;
        }
        $pdo->prepare('DELETE FROM remember_tokens WHERE id = ?')->execute([(int) $row['token_id']]);
        self::loginAs($user);
        self::issueRememberToken((int) $user['id']);
        Audit::log((int) $user['id'], 'login_resumed_via_remember', 'auth', (int) $user['id']);
    }

    /** Housekeeping helper used by tests/maintenance. */
    public static function purgeExpiredTokens(): int
    {
        $st = self::pdo()->prepare('DELETE FROM remember_tokens WHERE expires_at < NOW()');
        $st->execute();
        return $st->rowCount();
    }
}

/* --------------------------------------------------------------------- */
final class Rbac
{
    /** Permission matrix per PRD §4 roles. */
    private const MATRIX = [
        'view'        => ['ADMIN', 'EDITOR', 'READ_ONLY'],
        'export'      => ['ADMIN', 'EDITOR', 'READ_ONLY'],
        'create'      => ['ADMIN', 'EDITOR'],
        'edit'        => ['ADMIN', 'EDITOR'],
        'delete'      => ['ADMIN', 'EDITOR'],
        'verify'      => ['ADMIN', 'EDITOR'],
        'manage_users' => ['ADMIN'],
        'manage_settings' => ['ADMIN'],
    ];

    public static function can(?string $role, string $permission): bool
    {
        if ($role === null) {
            return false;
        }
        return in_array($role, self::MATRIX[$permission] ?? [], true);
    }

    /** Guard for controllers: 403 page (never blank) unless allowed. */
    public static function require(string $permission): void
    {
        if (!Auth::check()) {
            Session::flash('error', 'Please sign in first.');
            header('Location: /login', true, 302);
            exit;
        }
        if (!self::can(Auth::role(), $permission)) {
            http_response_code(403);
            render_view('errors/403', [
                'title' => 'Access denied',
                'message' => 'Your role (' . Auth::role() . ') cannot perform "' . $permission . '".',
            ]);
            exit;
        }
    }
}
