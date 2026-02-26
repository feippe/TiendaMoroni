<?php
declare(strict_types=1);

namespace TiendaMoroni\Models;

use TiendaMoroni\Core\Database as DB;

class UserModel
{
    public static function findById(int $id): array|false
    {
        return DB::fetchOne('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function findByEmail(string $email): array|false
    {
        return DB::fetchOne('SELECT * FROM users WHERE email = ?', [$email]);
    }

    public static function findByGoogleId(string $googleId): array|false
    {
        return DB::fetchOne('SELECT * FROM users WHERE google_id = ?', [$googleId]);
    }

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

    public static function update(int $id, array $data): void
    {
        $allowed = ['name', 'email', 'password_hash', 'avatar_url'];
        $sets    = [];
        $params  = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowed, true)) {
                $sets[]        = "`$key` = ?";
                $params[]      = $value;
            }
        }

        if (!$sets) return;

        $params[] = $id;
        DB::query('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?', $params);
    }

    public static function all(int $limit = 50, int $offset = 0): array
    {
        return DB::fetchAll(
            'SELECT id, name, email, role, auth_provider, created_at FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?',
            [$limit, $offset]
        );
    }
}
