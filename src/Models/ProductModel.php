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
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                    v.business_name AS vendor_name, v.slug AS vendor_slug
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN vendors v ON v.id = p.vendor_id
             WHERE p.slug = ?',
            [$slug]
        );
    }

    public static function featured(int $limit = 8): array
    {
        return DB::fetchAll(
            'SELECT p.*, v.business_name AS vendor_name
             FROM products p
             LEFT JOIN vendors v ON v.id = p.vendor_id
             WHERE p.status = "active" AND p.featured = 1
             ORDER BY p.created_at DESC LIMIT ?',
            [$limit]
        );
    }

    public static function recent(int $limit = 8): array
    {
        return DB::fetchAll(
            'SELECT p.*, v.business_name AS vendor_name
             FROM products p
             LEFT JOIN vendors v ON v.id = p.vendor_id
             WHERE p.status = "active"
             ORDER BY p.created_at DESC LIMIT ?',
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
            "SELECT p.*, c.name AS category_name, v.business_name AS vendor_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN vendors v ON v.id = p.vendor_id
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

    /**
     * Up to $limit products from the same vendor, excluding $excludeId.
     * Ordered: same category → sub-categories of that category → rest.
     * Within each group: featured first, then newest.
     */
    public static function byVendor(int $vendorId, int $excludeId, ?int $categoryId, int $limit = 8): array
    {
        return DB::fetchAll(
            'SELECT p.*, c.name AS category_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.vendor_id = ?
               AND p.id        != ?
               AND p.status     = "active"
             ORDER BY
               CASE WHEN p.category_id = ? THEN 0 ELSE 1 END ASC,
               CASE WHEN c.parent_id   = ? THEN 0 ELSE 1 END ASC,
               p.featured DESC,
               p.created_at DESC
             LIMIT ?',
            [$vendorId, $excludeId, $categoryId, $categoryId, $limit]
        );
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

    public static function all(int $limit = 50, int $offset = 0, string $q = '', ?int $vendorId = null, ?int $categoryId = null): array
    {
        $where  = ['1=1'];
        $params = [];

        if ($q) {
            $where[]  = '(p.name LIKE ? OR p.short_description LIKE ?)';
            $params[] = "%$q%";
            $params[] = "%$q%";
        }
        if ($vendorId) {
            $where[]  = 'p.vendor_id = ?';
            $params[] = $vendorId;
        }
        if ($categoryId) {
            $where[]  = 'p.category_id = ?';
            $params[] = $categoryId;
        }

        $whereStr = implode(' AND ', $where);
        $params[] = $limit;
        $params[] = $offset;

        return DB::fetchAll(
            "SELECT p.*, c.name AS category_name, v.business_name AS vendor_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN vendors v ON v.id = p.vendor_id
             WHERE $whereStr
             ORDER BY p.created_at DESC LIMIT ? OFFSET ?",
            $params
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
               vendor_id=:vendor_id, category_id=:category_id, name=:name, slug=:slug,
               description=:description, short_description=:short_description,
               price=:price, stock=:stock, status=:status, featured=:featured,
               main_image_url=:main_image_url, meta_title=:meta_title,
               meta_description=:meta_description
             WHERE id = :id',
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

        if (!empty($filters['category_ids']) && is_array($filters['category_ids'])) {
            $placeholders = implode(',', array_fill(0, count($filters['category_ids']), '?'));
            $where[]      = "p.category_id IN ($placeholders)";
            $params       = array_merge($params, array_map('intval', $filters['category_ids']));
        } elseif (!empty($filters['category_id'])) {
            $where[]  = 'p.category_id = ?';
            $params[] = (int) $filters['category_id'];
        }

        if (!empty($filters['vendor_id'])) {
            $where[]  = 'p.vendor_id = ?';
            $params[] = (int) $filters['vendor_id'];
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
