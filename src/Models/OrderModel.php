<?php
declare(strict_types=1);

namespace TiendaMoroni\Models;

use TiendaMoroni\Core\Database as DB;

class OrderModel
{
    public static function findById(int $id): array|false
    {
        return DB::fetchOne(
            'SELECT o.*, u.name AS buyer_name, u.email AS buyer_email
             FROM orders o
             JOIN users u ON u.id = o.user_id
             WHERE o.id = ?',
            [$id]
        );
    }

    public static function items(int $orderId): array
    {
        return DB::fetchAll(
            'SELECT oi.*, p.name AS product_name, p.slug AS product_slug, p.main_image_url
             FROM order_items oi
             JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = ?',
            [$orderId]
        );
    }

    public static function byUser(int $userId): array
    {
        return DB::fetchAll(
            'SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC',
            [$userId]
        );
    }

    public static function all(int $limit = 50, int $offset = 0, string $status = ''): array
    {
        if ($status) {
            return DB::fetchAll(
                'SELECT o.*, u.name AS buyer_name, u.email AS buyer_email
                 FROM orders o
                 JOIN users u ON u.id = o.user_id
                 WHERE o.status = ?
                 ORDER BY o.created_at DESC LIMIT ? OFFSET ?',
                [$status, $limit, $offset]
            );
        }

        return DB::fetchAll(
            'SELECT o.*, u.name AS buyer_name, u.email AS buyer_email
             FROM orders o
             JOIN users u ON u.id = o.user_id
             ORDER BY o.created_at DESC LIMIT ? OFFSET ?',
            [$limit, $offset]
        );
    }

    public static function count(): int
    {
        return (int) DB::fetchColumn('SELECT COUNT(*) FROM orders');
    }

    public static function create(array $data): int
    {
        DB::query(
            'INSERT INTO orders (user_id, vendor_id, subtotal, total, contact_phone, shipping_address, notes)
             VALUES (:user_id, :vendor_id, :subtotal, :total, :contact_phone, :shipping_address, :notes)',
            [
                ':user_id'          => $data['user_id'],
                ':vendor_id'        => $data['vendor_id'] ?? null,
                ':subtotal'         => $data['subtotal'],
                ':total'            => $data['total'],
                ':contact_phone'    => $data['contact_phone'],
                ':shipping_address' => $data['shipping_address'],
                ':notes'            => $data['notes'] ?? null,
            ]
        );
        return (int) DB::lastInsertId();
    }

    public static function addItem(int $orderId, array $item): void
    {
        DB::query(
            'INSERT INTO order_items (order_id, product_id, quantity, unit_price)
             VALUES (?, ?, ?, ?)',
            [$orderId, $item['product_id'], $item['quantity'], $item['unit_price']]
        );
    }

    public static function updateStatus(int $id, string $status): void
    {
        DB::query('UPDATE orders SET status = ? WHERE id = ?', [$status, $id]);
    }

    public static function stats(): array
    {
        return [
            'total_orders'   => (int) DB::fetchColumn('SELECT COUNT(*) FROM orders'),
            'pending_orders' => (int) DB::fetchColumn('SELECT COUNT(*) FROM orders WHERE status = "pending"'),
            'total_revenue'  => (float) DB::fetchColumn('SELECT COALESCE(SUM(total),0) FROM orders WHERE status != "cancelled"'),
            'total_products' => (int) DB::fetchColumn('SELECT COUNT(*) FROM products'),
        ];
    }
}
