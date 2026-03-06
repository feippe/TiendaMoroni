<?php
/**
 * ProductService – Consultas a la base de datos para el bot de WhatsApp.
 *
 * Todas las queries usan prepared statements (PDO) para prevenir SQL injection.
 * Los productos siempre se filtran por status = 'active'.
 *
 * Nota sobre product_retailer_id:
 *   El feed XML usa products.id como <g:id>, por lo tanto
 *   product_retailer_id en el catálogo de Meta = products.id en la BD.
 */

declare(strict_types=1);

class ProductService
{
    /**
     * Items por página en interactive lists.
     * Se reservan 2 slots para "Ver más" y "Volver" → máximo 8 items de contenido.
     */
    public const ITEMS_PER_LIST_PAGE = 8;

    /**
     * Productos por página en product_list messages.
     * Límite máximo de la Cloud API.
     */
    public const PRODUCTS_PER_PAGE = 30;

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ── Categorías ────────────────────────────────────────────────────────────

    /**
     * Devuelve categorías que tienen al menos un producto activo, paginadas.
     */
    public function getActiveCategories(int $offset = 0, int $limit = self::ITEMS_PER_LIST_PAGE): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.id, c.name, COUNT(p.id) AS product_count
             FROM categories c
             INNER JOIN products p ON p.category_id = c.id AND p.status = :status
             GROUP BY c.id, c.name
             ORDER BY c.sort_order ASC, c.name ASC
             LIMIT :lim OFFSET :off'
        );
        $stmt->bindValue(':status', 'active');
        $stmt->bindValue(':lim',    $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':off',    $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Total de categorías con al menos un producto activo.
     */
    public function countActiveCategories(): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(DISTINCT c.id)
             FROM categories c
             INNER JOIN products p ON p.category_id = c.id AND p.status = ?'
        );
        $stmt->execute(['active']);
        return (int)$stmt->fetchColumn();
    }

    // ── Vendedores ────────────────────────────────────────────────────────────

    /**
     * Devuelve vendedores con al menos un producto activo, paginados.
     */
    public function getActiveVendors(int $offset = 0, int $limit = self::ITEMS_PER_LIST_PAGE): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT v.id, v.business_name AS name, v.phone, COUNT(p.id) AS product_count
             FROM vendors v
             INNER JOIN products p ON p.vendor_id = v.id AND p.status = :status
             GROUP BY v.id, v.business_name, v.phone
             ORDER BY v.business_name ASC
             LIMIT :lim OFFSET :off'
        );
        $stmt->bindValue(':status', 'active');
        $stmt->bindValue(':lim',    $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':off',    $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Total de vendedores con al menos un producto activo.
     */
    public function countActiveVendors(): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(DISTINCT v.id)
             FROM vendors v
             INNER JOIN products p ON p.vendor_id = v.id AND p.status = ?'
        );
        $stmt->execute(['active']);
        return (int)$stmt->fetchColumn();
    }

    // ── Productos por categoría ───────────────────────────────────────────────

    /**
     * Devuelve productos activos de una categoría, con datos del vendedor.
     */
    public function getProductsByCategory(
        int $categoryId,
        int $offset = 0,
        int $limit  = self::PRODUCTS_PER_PAGE
    ): array {
        $stmt = $this->pdo->prepare(
            'SELECT p.id, p.name, p.slug, p.price, p.stock, p.main_image_url,
                    v.id AS vendor_id, v.business_name AS vendor_name, v.phone AS vendor_phone
             FROM products p
             INNER JOIN vendors v ON v.id = p.vendor_id
             WHERE p.category_id = :cat AND p.status = :status AND p.stock > 0
             ORDER BY p.featured DESC, p.created_at DESC
             LIMIT :lim OFFSET :off'
        );
        $stmt->bindValue(':cat',    $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'active');
        $stmt->bindValue(':lim',    $limit,      PDO::PARAM_INT);
        $stmt->bindValue(':off',    $offset,     PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countProductsByCategory(int $categoryId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM products WHERE category_id = ? AND status = ? AND stock > 0'
        );
        $stmt->execute([$categoryId, 'active']);
        return (int)$stmt->fetchColumn();
    }

    // ── Productos por vendedor ────────────────────────────────────────────────

    /**
     * Devuelve productos activos de un vendedor.
     */
    public function getProductsByVendor(
        int $vendorId,
        int $offset = 0,
        int $limit  = self::PRODUCTS_PER_PAGE
    ): array {
        $stmt = $this->pdo->prepare(
            'SELECT p.id, p.name, p.slug, p.price, p.stock, p.main_image_url,
                    v.id AS vendor_id, v.business_name AS vendor_name, v.phone AS vendor_phone
             FROM products p
             INNER JOIN vendors v ON v.id = p.vendor_id
             WHERE p.vendor_id = :vid AND p.status = :status AND p.stock > 0
             ORDER BY p.featured DESC, p.created_at DESC
             LIMIT :lim OFFSET :off'
        );
        $stmt->bindValue(':vid',    $vendorId, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'active');
        $stmt->bindValue(':lim',    $limit,    PDO::PARAM_INT);
        $stmt->bindValue(':off',    $offset,   PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countProductsByVendor(int $vendorId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM products WHERE vendor_id = ? AND status = ? AND stock > 0'
        );
        $stmt->execute([$vendorId, 'active']);
        return (int)$stmt->fetchColumn();
    }

    // ── Búsqueda por texto ────────────────────────────────────────────────────

    /**
     * Busca productos activos cuyo nombre o descripción contengan el término.
     * Usa LIKE en lugar de FULLTEXT para compatibilidad con cualquier charset.
     */
    public function searchProducts(
        string $term,
        int    $offset = 0,
        int    $limit  = self::PRODUCTS_PER_PAGE
    ): array {
        $like = '%' . $term . '%';
        $stmt = $this->pdo->prepare(
            'SELECT p.id, p.name, p.slug, p.price, p.stock, p.main_image_url,
                    v.id AS vendor_id, v.business_name AS vendor_name, v.phone AS vendor_phone
             FROM products p
             INNER JOIN vendors v ON v.id = p.vendor_id
             WHERE (p.name LIKE :t1 OR p.description LIKE :t2) AND p.status = :status AND p.stock > 0
             ORDER BY p.featured DESC, p.created_at DESC
             LIMIT :lim OFFSET :off'
        );
        $stmt->bindValue(':t1',     $like,   PDO::PARAM_STR);
        $stmt->bindValue(':t2',     $like,   PDO::PARAM_STR);
        $stmt->bindValue(':status', 'active');
        $stmt->bindValue(':lim',    $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':off',    $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countSearchProducts(string $term): int
    {
        $like = '%' . $term . '%';
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM products
             WHERE (name LIKE ? OR description LIKE ?) AND status = ? AND stock > 0'
        );
        $stmt->execute([$like, $like, 'active']);
        return (int)$stmt->fetchColumn();
    }

    // ── Consultas individuales ────────────────────────────────────────────────

    /**
     * Obtiene un producto activo por su ID, con datos del vendedor.
     */
    public function getProductById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.id, p.name, p.price, p.description, p.main_image_url,
                    v.id AS vendor_id, v.business_name AS vendor_name, v.phone AS vendor_phone
             FROM products p
             INNER JOIN vendors v ON v.id = p.vendor_id
             WHERE p.id = ? AND p.status = ?'
        );
        $stmt->execute([$id, 'active']);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Obtiene vendedor por ID.
     */
    public function getVendorById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, business_name AS name, phone FROM vendors WHERE id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Obtiene el nombre de una categoría.
     */
    public function getCategoryName(int $id): string
    {
        $stmt = $this->pdo->prepare('SELECT name FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        return (string)($stmt->fetchColumn() ?: 'Categoría');
    }

    /**
     * Obtiene el nombre comercial de un vendedor.
     */
    public function getVendorName(int $id): string
    {
        $stmt = $this->pdo->prepare('SELECT business_name FROM vendors WHERE id = ?');
        $stmt->execute([$id]);
        return (string)($stmt->fetchColumn() ?: 'Artesano');
    }

    /**
     * Obtiene productos a partir de sus product_retailer_id (= products.id en el feed).
     * Usado para procesar order messages de WhatsApp.
     *
     * @param  array  $retailerIds  Array de IDs numéricos como strings.
     * @return array                Filas de productos con datos del vendedor.
     */
    public function getProductsByRetailerIds(array $retailerIds): array
    {
        if (empty($retailerIds)) {
            return [];
        }

        // Filtrar solo valores numéricos para evitar injection
        $ids = array_filter($retailerIds, fn($id) => ctype_digit((string)$id));
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT p.id, p.name, p.price, p.main_image_url,
                    v.id AS vendor_id, v.business_name AS vendor_name, v.phone AS vendor_phone
             FROM products p
             INNER JOIN vendors v ON v.id = p.vendor_id
             WHERE p.id IN ($placeholders)"
        );
        $stmt->execute(array_values($ids));
        return $stmt->fetchAll();
    }
}
