<?php
declare(strict_types=1);

namespace TiendaMoroni\Models;

use TiendaMoroni\Core\Database as DB;

class CategoryModel
{
    public static function all(): array
    {
        return DB::fetchAll(
            'SELECT c.*, p.name AS parent_name
             FROM categories c
             LEFT JOIN categories p ON p.id = c.parent_id
             ORDER BY c.sort_order ASC, c.name ASC'
        );
    }

    public static function roots(): array
    {
        return DB::fetchAll(
            'SELECT * FROM categories WHERE parent_id IS NULL ORDER BY sort_order ASC, name ASC'
        );
    }

    public static function findById(int $id): array|false
    {
        return DB::fetchOne('SELECT * FROM categories WHERE id = ?', [$id]);
    }

    public static function findBySlug(string $slug): array|false
    {
        return DB::fetchOne('SELECT * FROM categories WHERE slug = ?', [$slug]);
    }

    public static function withProductCount(): array
    {
        return DB::fetchAll(
            'SELECT c.*, COUNT(p.id) AS product_count
             FROM categories c
             LEFT JOIN products p ON p.category_id = c.id AND p.status = "active"
             WHERE c.parent_id IS NULL
             GROUP BY c.id
             ORDER BY c.sort_order ASC, c.name ASC'
        );
    }

    public static function create(array $data): int
    {
        DB::query(
            'INSERT INTO categories (name, slug, description, image_url, parent_id, meta_title, meta_description, sort_order)
             VALUES (:name, :slug, :description, :image_url, :parent_id, :meta_title, :meta_description, :sort_order)',
            [
                ':name'             => $data['name'],
                ':slug'             => $data['slug'],
                ':description'      => $data['description'] ?? null,
                ':image_url'        => $data['image_url'] ?? null,
                ':parent_id'        => $data['parent_id'] ?? null,
                ':meta_title'       => $data['meta_title'] ?? null,
                ':meta_description' => $data['meta_description'] ?? null,
                ':sort_order'       => $data['sort_order'] ?? 0,
            ]
        );
        return (int) DB::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        DB::query(
            'UPDATE categories SET name=:name, slug=:slug, description=:description,
             image_url=:image_url, parent_id=:parent_id, meta_title=:meta_title,
             meta_description=:meta_description, sort_order=:sort_order
             WHERE id = :id',
            [
                ':name'             => $data['name'],
                ':slug'             => $data['slug'],
                ':description'      => $data['description'] ?? null,
                ':image_url'        => $data['image_url'] ?? null,
                ':parent_id'        => $data['parent_id'] ?? null,
                ':meta_title'       => $data['meta_title'] ?? null,
                ':meta_description' => $data['meta_description'] ?? null,
                ':sort_order'       => $data['sort_order'] ?? 0,
                ':id'               => $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        DB::query('DELETE FROM categories WHERE id = ?', [$id]);
    }

    /** Direct children of a category. */
    public static function children(int $parentId): array
    {
        return DB::fetchAll(
            'SELECT * FROM categories WHERE parent_id = ? ORDER BY sort_order ASC, name ASC',
            [$parentId]
        );
    }

    /**
     * Returns all IDs that belong to the subtree rooted at $categoryId
     * (including $categoryId itself).
     */
    public static function descendantIds(int $categoryId): array
    {
        $all = DB::fetchAll('SELECT id, parent_id FROM categories');

        $ids     = [$categoryId];
        $changed = true;
        while ($changed) {
            $changed = false;
            foreach ($all as $row) {
                $rowId  = (int) $row['id'];
                $rowPid = $row['parent_id'] !== null ? (int) $row['parent_id'] : null;
                if (!in_array($rowId, $ids, true) && $rowPid !== null && in_array($rowPid, $ids, true)) {
                    $ids[]   = $rowId;
                    $changed = true;
                }
            }
        }

        return $ids;
    }

    /**
     * Returns every category as a flat list ordered for tree rendering,
     * with an added 'depth' key (0 = root).
     */
    public static function tree(): array
    {
        $all = DB::fetchAll(
            'SELECT * FROM categories ORDER BY sort_order ASC, name ASC'
        );

        $byParent = [];
        foreach ($all as $cat) {
            $key              = $cat['parent_id'] !== null ? (int) $cat['parent_id'] : 0;
            $byParent[$key][] = $cat;
        }

        $result = [];
        self::flattenTree($byParent, 0, 0, $result);
        return $result;
    }

    private static function flattenTree(array $byParent, int $parentId, int $depth, array &$result): void
    {
        if (!isset($byParent[$parentId])) {
            return;
        }
        foreach ($byParent[$parentId] as $cat) {
            $cat['depth'] = $depth;
            $result[]     = $cat;
            self::flattenTree($byParent, (int) $cat['id'], $depth + 1, $result);
        }
    }
}
