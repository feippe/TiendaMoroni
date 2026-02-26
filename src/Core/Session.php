<?php
declare(strict_types=1);

namespace TiendaMoroni\Core;

class Session
{
    private static ?array $currentUser = null;

    /**
     * Start or resume a session based on the cookie token.
     */
    public static function start(): void
    {
        if (!isset($_COOKIE[SESSION_COOKIE])) {
            return;
        }

        $token = $_COOKIE[SESSION_COOKIE];
        $session = Database::fetchOne(
            'SELECT s.*, u.id as user_id, u.name, u.email, u.role, u.avatar_url
             FROM sessions s
             JOIN users u ON u.id = s.user_id
             WHERE s.token = ? AND s.expires_at > NOW()',
            [$token]
        );

        if ($session) {
            self::$currentUser = $session;
        } else {
            // Expired or invalid cookie – clean it up
            self::clearCookie();
        }
    }

    /**
     * Create a new session for a user and set the cookie.
     */
    public static function create(array $user): void
    {
        $token     = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);

        Database::query(
            'INSERT INTO sessions (token, user_id, expires_at) VALUES (?, ?, ?)',
            [$token, $user['id'], $expiresAt]
        );

        setcookie(
            SESSION_COOKIE,
            $token,
            [
                'expires'  => time() + SESSION_LIFETIME,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            ]
        );

        self::$currentUser = array_merge($user, [
            'token'      => $token,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Destroy the current session.
     */
    public static function destroy(): void
    {
        if (isset($_COOKIE[SESSION_COOKIE])) {
            Database::query(
                'DELETE FROM sessions WHERE token = ?',
                [$_COOKIE[SESSION_COOKIE]]
            );
        }

        self::clearCookie();
        self::$currentUser = null;
    }

    public static function user(): ?array
    {
        return self::$currentUser;
    }

    public static function isLoggedIn(): bool
    {
        return self::$currentUser !== null;
    }

    public static function isAdmin(): bool
    {
        return self::$currentUser !== null && (self::$currentUser['role'] ?? '') === 'admin';
    }

    public static function requireAuth(string $redirectBack = ''): void
    {
        if (!self::isLoggedIn()) {
            $url = '/auth/login';
            if ($redirectBack) {
                $url .= '?redirect=' . urlencode($redirectBack);
            }
            redirect($url);
        }
    }

    public static function requireAdminAuth(string $redirectBack = ''): void
    {
        if (!self::isLoggedIn()) {
            $url = '/admin/login';
            if ($redirectBack) {
                $url .= '?redirect=' . urlencode($redirectBack);
            }
            redirect($url);
        }
    }

    public static function requireAdmin(): void
    {
        self::requireAdminAuth('/admin');

        if (!self::isAdmin()) {
            http_response_code(403);
            die('Acceso denegado.');
        }
    }

    private static function clearCookie(): void
    {
        setcookie(SESSION_COOKIE, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        if (isset($_COOKIE[SESSION_COOKIE])) {
            unset($_COOKIE[SESSION_COOKIE]);
        }
    }

    // ── Flash messages ────────────────────────────────────────────────────────

    public static function flash(string $key, mixed $value): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash'][$key] = $value;
    }

    public static function getFlash(string $key): mixed
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $value = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $value;
    }
}
