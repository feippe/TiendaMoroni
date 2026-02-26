<?php
declare(strict_types=1);

namespace TiendaMoroni\Models;

use TiendaMoroni\Core\Database as DB;

class ProductModel
{
    // ── Read ──────────────────────────────────────────────────────────────────

    public static function findById(int $id): array|false
    {
        return DB::fetchOne(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.id = ?',
            [$id]
        );
    }

    public static function findBySlug(string $slug): array|false
    {
        return DB::fetchOne(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.slug = ?',
            [$slug]
        );
    }

    public static function featured(int $limit = 8): array
    {
        return DB::fetchAll(
            'SELECT * FROM products WHERE status = "active" AND featured = 1 ORDER BY created_at DESC LIMIT ?',
            [$limit]
        );
    }

    public static function recent(int $limit = 8): array
    {
        return DB::fetchAll(
            'SELECT * FROM products WHERE status = "active" ORDER BY created_at DESC LIMIT ?',
            [$limit]
        );
    }

    /**
     * Paginated list with optional filters.
     *
     * @param array $filters  Keys: category_id, q (search), min_price, max_price, sort
     */
    public static function list(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        [$where, $params] = self::buildFilterWhere($filters);

        $sort = match ($filters['sort'] ?? '') {
            'price_asc'  => 'p.price ASC',
            'price_desc' => 'p.price DESC',
            'newest'     => 'p.created_at DESC',
            default      => 'p.featured DESC, p.created_at DESC',
        };

        $params[] = $limit;
        $params[] = $offset;

        return DB::fetchAll(
            "SELECT p.*, c.name AS category_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE $where
             ORDER BY $sort
             LIMIT ? OFFSET ?",
            $params
        );
    }

    public static function count(array $filters = []): int
    {
        [$where, $params] = self::buildFilterWhere($filters);

        return (int) DB::fetchColumn(
            "SELECT COUNT(*) FROM products p WHERE $where",
            $params
        );
    }

    public static function byCategory(int $categoryId, int $limit = 20, int $offset = 0): array
    {
        return self::list(['category_id' => $categoryId], $limit, $offset);
    }

    // ── Images ────────────────────────────────────────────────────────────────

    public static function images(int $productId): array
    {
        return DB::fetchAll(
            'SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC',
            [$productId]
        );
    }

    // ── Admin all ─────────────────────────────────────────────────────────────

    public static function all(int $limit = 50, int $offset = 0, string $q = ''): array
    {
        if ($q) {
            return DB::fetchAll(
                'SELECT p.*, c.name AS category_name
                 FROM products p
                 LEFT JOIN categories c ON c.id = p.category_id
                 WHERE p.name LIKE ? OR p.short_description LIKE ?
                 ORDER BY p.created_at DESC LIMIT ? OFFSET ?',
                ["%$q%", "%$q%", $limit, $offset]
            );
        }

        return DB::fetchAll(
            'SELECT p.*, c.name AS category_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             ORDER BY p.created_at DESC LIMIT ? OFFSET ?',
            [$limit, $offset]
        );
    }

    // ── Create / Update / Delete ──────────────────────────────────────────────

    public static function create(array $data): int
    {
        DB::query(
            'INSERT INTO products
               (vendor_id, category_id, name, slug, description, short_description,
                price, stock, status, featured, main_image_url, meta_title, meta_description)
             VALUES
               (:vendor_id, :category_id, :name, :slug, :description, :short_description,
                :price, :stock, :status, :featured, :main_image_url, :meta_title, :meta_description)',
            [
                ':vendor_id'         => $data['vendor_id'],
                ':category_id'       => $data['category_id'] ?? null,
                ':name'              => $data['name'],
                ':slug'              => $data['slug'],
                ':description'       => $data['description'] ?? null,
                ':short_description' => $data['short_description'] ?? null,
                ':price'             => $data['price'],
                ':stock'             => $data['stock'] ?? 0,
                ':status'            => $data['status'] ?? 'draft',
                ':featured'          => $data['featured'] ?? 0,
                ':main_image_url'    => $data['main_image_url'] ?? null,
                ':meta_title'        => $data['meta_title'] ?? null,
                ':meta_description'  => $data['meta_description'] ?? null,
            ]
        );
        return (int) DB::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        DB::query(
            'UPDATE products SET
               category_id=:category_id, name=:name, slug=:slug,
               description=:description, short_description=:short_description,
               price=:price, stock=:stock, status=:status, featured=:featured,
               main_image_url=:main_image_url, meta_title=:meta_title,
               meta_description=:meta_description
             WHERE id = :id',
            [
                ':category_id'       => $data['category_id'] ?? null,
                ':name'              => $data['name'],
                ':slug'              => $data['slug'],
                ':description'       => $data['description'] ?? null,
                ':short_description' => $data['short_description'] ?? null,
                ':price'             => $data['price'],
                ':stock'             => $data['stock'] ?? 0,
                ':status'            => $data['status'] ?? 'draft',
                ':featured'          => $data['featured'] ?? 0,
                ':main_image_url'    => $data['main_image_url'] ?? null,
                ':meta_title'        => $data['meta_title'] ?? null,
                ':meta_description'  => $data['meta_description'] ?? null,
                ':id'                => $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        DB::query('DELETE FROM products WHERE id = ?', [$id]);
    }

    public static function addImage(int $productId, string $imageUrl, int $sortOrder = 0): void
    {
        DB::query(
            'INSERT INTO product_images (product_id, image_url, sort_order) VALUES (?, ?, ?)',
            [$productId, $imageUrl, $sortOrder]
        );
    }

    public static function deleteImages(int $productId): void
    {
        DB::query('DELETE FROM product_images WHERE product_id = ?', [$productId]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private static function buildFilterWhere(array $filters): array
    {
        $where  = ['p.status = "active"'];
        $params = [];

        if (!empty($filters['category_id'])) {
            $where[]  = 'p.category_id = ?';
            $params[] = (int) $filters['category_id'];
        }

        if (!empty($filters['q'])) {
            $where[]  = '(p.name LIKE ? OR p.short_description LIKE ?)';
            $params[] = '%' . $filters['q'] . '%';
            $params[] = '%' . $filters['q'] . '%';
        }

        if (isset($filters['min_price']) && $filters['min_price'] !== '') {
            $where[]  = 'p.price >= ?';
            $params[] = (float) $filters['min_price'];
        }

        if (isset($filters['max_price']) && $filters['max_price'] !== '') {
            $where[]  = 'p.price <= ?';
            $params[] = (float) $filters['max_price'];
        }

        return [implode(' AND ', $where), $params];
    }
}
