<?php
declare(strict_types=1);

namespace TiendaMoroni\Models;

use TiendaMoroni\Core\Database as DB;

class UserModel
{
    /** Columns safe to expose — never includes password_hash. */
    private const SAFE_COLS = 'id, name, email, avatar_url, auth_provider, role, active, created_at';

    // ── Basic lookups ────────────────────────────────────────────────────────

    public static function findById(int $id): array|false
    {
        return DB::fetchOne(
            'SELECT ' . self::SAFE_COLS . ' FROM users WHERE id = ?',
            [$id]
        );
    }

    /** Used for auth — includes password_hash intentionally. */
    public static function findByEmailWithPassword(string $email): array|false
    {
        return DB::fetchOne('SELECT * FROM users WHERE email = ?', [$email]);
    }

    /** @deprecated Use findByEmailWithPassword for auth, findByEmail for display */
    public static function findByEmail(string $email): array|false
    {
        return DB::fetchOne(
            'SELECT ' . self::SAFE_COLS . ' FROM users WHERE email = ?',
            [$email]
        );
    }

    public static function findByGoogleId(string $googleId): array|false
    {
        return DB::fetchOne('SELECT * FROM users WHERE google_id = ?', [$googleId]);
    }

    // ── Admin: listing with filters & pagination ─────────────────────────────

    /**
     * @param array{q?: string, role?: string} $filters
     */
    public static function findAll(array $filters, int $limit, int $offset): array
    {
        [$where, $params] = self::buildWhere($filters);
        return DB::fetchAll(
            'SELECT ' . self::SAFE_COLS . " FROM users $where ORDER BY created_at DESC LIMIT ? OFFSET ?",
            [...$params, $limit, $offset]
        );
    }

    public static function countAll(array $filters): int
    {
        [$where, $params] = self::buildWhere($filters);
        $row = DB::fetchOne("SELECT COUNT(*) AS n FROM users $where", $params);
        return (int) ($row['n'] ?? 0);
    }

    public static function countByActive(int $active): int
    {
        $row = DB::fetchOne('SELECT COUNT(*) AS n FROM users WHERE active = ? AND role != ?', [$active, 'vendor']);
        return (int) ($row['n'] ?? 0);
    }

    // ── Mutations ────────────────────────────────────────────────────────────

    public static function create(array $data): int
    {
        DB::query(
            'INSERT INTO users (name, email, password_hash, avatar_url, auth_provider, google_id, role)
             VALUES (:name, :email, :password_hash, :avatar_url, :auth_provider, :google_id, :role)',
            [
                ':name'          => $data['name'],
                ':email'         => $data['email'],
                ':password_hash' => $data['password_hash'] ?? null,
                ':avatar_url'    => $data['avatar_url'] ?? null,
                ':auth_provider' => $data['auth_provider'] ?? 'own',
                ':google_id'     => $data['google_id'] ?? null,
                ':role'          => $data['role'] ?? 'buyer',
            ]
        );
        return (int) DB::lastInsertId();
    }

    /**
     * Admin update — allows name and role only; never touches email or password.
     */
    public static function update(int $id, array $data): void
    {
        $allowed = ['name', 'role', 'avatar_url'];
        $sets   = [];
        $params = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowed, true)) {
                $sets[]   = "`$key` = ?";
                $params[] = $value;
            }
        }

        if (!$sets) return;

        $params[] = $id;
        DB::query('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?', $params);
    }

    /**
     * Toggle the active flag. Returns the new active value (0 or 1).
     */
    public static function toggleActive(int $id): int
    {
        DB::query('UPDATE users SET active = 1 - active WHERE id = ?', [$id]);
        $row = DB::fetchOne('SELECT active FROM users WHERE id = ?', [$id]);
        return (int) ($row['active'] ?? 1);
    }

    public static function delete(int $id): void
    {
        DB::query('DELETE FROM users WHERE id = ?', [$id]);
    }

    public static function hasOrders(int $id): bool
    {
        $row = DB::fetchOne(
            'SELECT COUNT(*) AS n FROM orders WHERE user_id = ?',
            [$id]
        );
        return (int) ($row['n'] ?? 0) > 0;
    }

    // ── Legacy ──────────────────────────────────────────────────────────────

    public static function all(int $limit = 50, int $offset = 0): array
    {
        return DB::fetchAll(
            'SELECT ' . self::SAFE_COLS . ' FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?',
            [$limit, $offset]
        );
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private static function buildWhere(array $filters): array
    {
        $conditions = [];
        $params     = [];

        if (!empty($filters['q'])) {
            $conditions[] = '(name LIKE ? OR email LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            $params[]     = $like;
            $params[]     = $like;
        }

        if (!empty($filters['role']) && in_array($filters['role'], ['admin', 'buyer', 'vendor'], true)) {
            $conditions[] = 'role = ?';
            $params[]     = $filters['role'];
        } else {
            // By default, exclude vendor accounts — they are managed via the vendors section.
            $conditions[] = 'role != ?';
            $params[]     = 'vendor';
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        return [$where, $params];
    }
}
