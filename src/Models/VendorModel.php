<?php
declare(strict_types=1);

namespace TiendaMoroni\Models;

use TiendaMoroni\Core\Database as DB;

class VendorModel
{
    public static function all(int $limit = 50, int $offset = 0): array
    {
        return DB::fetchAll(
            'SELECT v.*, u.name AS user_name, u.email AS user_email
             FROM vendors v
             JOIN users u ON u.id = v.user_id
             ORDER BY v.created_at DESC LIMIT ? OFFSET ?',
            [$limit, $offset]
        );
    }

    public static function findById(int $id): array|false
    {
        return DB::fetchOne(
            'SELECT v.*, u.name AS user_name, u.email AS user_email
             FROM vendors v
             JOIN users u ON u.id = v.user_id
             WHERE v.id = ?',
            [$id]
        );
    }

    public static function findBySlug(string $slug): array|false
    {
        return DB::fetchOne(
            'SELECT v.*, u.name AS user_name, u.email AS user_email
             FROM vendors v
             JOIN users u ON u.id = v.user_id
             WHERE v.slug = ?',
            [$slug]
        );
    }

    public static function findByUserId(int $userId): array|false
    {
        return DB::fetchOne('SELECT * FROM vendors WHERE user_id = ?', [$userId]);
    }

    public static function first(): array|false
    {
        return DB::fetchOne('SELECT * FROM vendors LIMIT 1');
    }

    public static function allForSelect(): array
    {
        return DB::fetchAll(
            'SELECT id, business_name, slug FROM vendors ORDER BY business_name ASC'
        );
    }

    public static function create(array $data): int
    {
        DB::query(
            'INSERT INTO vendors (user_id, business_name, slug, business_description, email, phone, is_verified)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['user_id'],
                $data['business_name'],
                $data['slug'],
                $data['business_description'] ?? null,
                $data['email'],
                $data['phone'] ?? null,
                $data['is_verified'] ?? 0,
            ]
        );
        return (int) DB::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        DB::query(
            'UPDATE vendors SET business_name=?, slug=?, business_description=?, email=?, phone=?, is_verified=? WHERE id=?',
            [
                $data['business_name'],
                $data['slug'],
                $data['business_description'] ?? null,
                $data['email'],
                $data['phone'] ?? null,
                $data['is_verified'] ?? 0,
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        DB::query('DELETE FROM vendors WHERE id = ?', [$id]);
    }
}
