<?php
declare(strict_types=1);

namespace TiendaMoroni\Core;

/**
 * Cart stored in PHP native session.
 *
 * Structure: $_SESSION['cart'] = [
 *   product_id => ['qty' => int, 'price' => float, 'name' => string, 'image' => string]
 * ]
 */
class Cart
{
    private static string $key = 'cart';

    private static function boot(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION[self::$key])) {
            $_SESSION[self::$key] = [];
        }
    }

    public static function add(int $productId, float $price, string $name, string $image, int $qty = 1): void
    {
        self::boot();

        if (isset($_SESSION[self::$key][$productId])) {
            $_SESSION[self::$key][$productId]['qty'] += $qty;
        } else {
            $_SESSION[self::$key][$productId] = [
                'qty'   => $qty,
                'price' => $price,
                'name'  => $name,
                'image' => $image,
            ];
        }
    }

    public static function update(int $productId, int $qty): void
    {
        self::boot();

        if ($qty <= 0) {
            self::remove($productId);
            return;
        }

        if (isset($_SESSION[self::$key][$productId])) {
            $_SESSION[self::$key][$productId]['qty'] = $qty;
        }
    }

    public static function remove(int $productId): void
    {
        self::boot();
        unset($_SESSION[self::$key][$productId]);
    }

    public static function clear(): void
    {
        self::boot();
        $_SESSION[self::$key] = [];
    }

    public static function items(): array
    {
        self::boot();
        return $_SESSION[self::$key];
    }

    public static function count(): int
    {
        self::boot();
        return array_sum(array_column($_SESSION[self::$key], 'qty'));
    }

    public static function subtotal(): float
    {
        self::boot();
        $total = 0.0;
        foreach ($_SESSION[self::$key] as $item) {
            $total += $item['price'] * $item['qty'];
        }
        return $total;
    }

    public static function isEmpty(): bool
    {
        self::boot();
        return empty($_SESSION[self::$key]);
    }
}
