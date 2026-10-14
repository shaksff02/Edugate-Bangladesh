<?php
/**
 * UMS EduGate — Auth controller (Phase 3)
 * Routes: /login (GET/POST), /logout (POST), /dashboard (GET, role-aware).
 */

declare(strict_types=1);

require_once BASE_PATH . '/app/Core/Session.php';
require_once BASE_PATH . '/app/Core/Csrf.php';
require_once BASE_PATH . '/app/Core/Auth.php';

final class AuthController
{
    /** GET /login — form (or redirect if already signed in). */
    public function showLogin(): void
    {
        Session::start();
        Auth::resumeFromCookie();
        if (Auth::check()) {
            header('Location: /dashboard', true, 302);
            return;
        }
        render_view('auth/login', [
            'title'   => 'Sign in — UMS EduGate',
            'error'   => Session::flash('error'),
            'success' => Session::flash('success'),
            'old'     => '',
        ]);
    }

    /** POST /login — credential check with throttling + CSRF. */
    public function login(): void
    {
        Session::start();
        Csrf::check();

        $email    = (string) ($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $remember = isset($_POST['remember']);

        $result = Auth::attempt($email, $password, $remember);
        if (!$result['ok']) {
            Session::flash('error', $result['message']);
            render_view('auth/login', [
                'title'   => 'Sign in — UMS EduGate',
                'error'   => $result['message'],
                'success' => null,
                'old'     => $email,
            ], 401);
            return;
        }
        Session::flash('success', $result['message']);
        header('Location: /dashboard', true, 302);
    }

    /** POST /logout — destroy session and revoke remember tokens. */
    public function logout(): void
    {
        Session::start();
        Csrf::check();
        Auth::logout();
        Session::start(); // fresh session so the flash message survives
        Session::flash('success', 'You have been signed out.');
        header('Location: /login', true, 302);
    }

    /** GET /dashboard — protected landing page showing the role badge. */
    public function dashboard(): void
    {
        Session::start();
        Auth::resumeFromCookie();
        Rbac::require('view'); // redirects to /login when guest

        $canManageUsers = Rbac::can(Auth::role(), 'manage_users');
        $recentLogins = [];
        if ($canManageUsers) {
            try {
                $st = Auth::pdo()->prepare(
                    'SELECT email_attempted, ip_address, success, created_at
                     FROM login_attempts ORDER BY id DESC LIMIT 10'
                );
                $st->execute();
                $recentLogins = $st->fetchAll();
            } catch (Throwable $e) {
                error_log('[UMS EduGate] login_attempts read failed: ' . $e->getMessage());
            }
        }

        render_view('dashboard/index', [
            'title'       => 'Dashboard — UMS EduGate',
            'breadcrumbs' => [['label' => 'Home', 'url' => '/'], ['label' => 'Dashboard']],
            'userName'    => Auth::name(),
            'role'        => Auth::role(),
            'recentLogins' => $recentLogins,
        ]);
    }
}
