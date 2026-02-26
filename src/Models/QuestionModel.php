<?php
declare(strict_types=1);

namespace TiendaMoroni\Models;

use TiendaMoroni\Core\Database as DB;

class QuestionModel
{
    public static function byProduct(int $productId, bool $publicOnly = true): array
    {
        $extra = $publicOnly ? 'AND q.is_public = 1' : '';

        return DB::fetchAll(
            "SELECT q.*, u.name AS user_name
             FROM product_questions q
             JOIN users u ON u.id = q.user_id
             WHERE q.product_id = ? $extra
             ORDER BY q.created_at ASC",
            [$productId]
        );
    }

    public static function all(int $limit = 50, int $offset = 0): array
    {
        return DB::fetchAll(
            'SELECT q.*, u.name AS user_name, p.name AS product_name, p.slug AS product_slug
             FROM product_questions q
             JOIN users u ON u.id = q.user_id
             JOIN products p ON p.id = q.product_id
             ORDER BY q.answered_at IS NULL DESC, q.created_at DESC
             LIMIT ? OFFSET ?',
            [$limit, $offset]
        );
    }

    public static function unansweredCount(): int
    {
        return (int) DB::fetchColumn(
            'SELECT COUNT(*) FROM product_questions WHERE answer IS NULL'
        );
    }

    public static function findById(int $id): array|false
    {
        return DB::fetchOne('SELECT * FROM product_questions WHERE id = ?', [$id]);
    }

    public static function create(int $productId, int $userId, string $question): int
    {
        DB::query(
            'INSERT INTO product_questions (product_id, user_id, question) VALUES (?, ?, ?)',
            [$productId, $userId, $question]
        );
        return (int) DB::lastInsertId();
    }

    public static function answer(int $id, string $answer): void
    {
        DB::query(
            'UPDATE product_questions SET answer = ?, answered_at = NOW() WHERE id = ?',
            [$answer, $id]
        );
    }

    public static function delete(int $id): void
    {
        DB::query('DELETE FROM product_questions WHERE id = ?', [$id]);
    }
}
