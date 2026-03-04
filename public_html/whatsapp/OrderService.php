<?php
/**
 * OrderService – Registra pedidos realizados a través de WhatsApp.
 *
 * Los pedidos de WhatsApp se guardan en `wa_orders`, separados de la tabla
 * `orders` principal, porque los clientes de WhatsApp no necesitan cuenta
 * registrada en el sistema.
 *
 * Flujo:
 *   1. Cliente envía su carrito (order message) → createFromOrderMessage()
 *   2. Cliente indica interés sin carrito         → createInterestOrder()
 *   3. El bot notifica al vendedor y registra el pedido con status 'pending'.
 */

declare(strict_types=1);

class OrderService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Registra todos los items de un order message (carrito enviado por WhatsApp).
     *
     * @param string $phone      Número del comprador (formato internacional).
     * @param array  $items      Items del order message:
     *                           [['product_retailer_id', 'quantity', 'item_price', 'currency'], ...]
     * @param string $catalogId  ID del catálogo de Meta Commerce Manager.
     * @param array  $productMap Productos resueltos desde la BD, indexados por retailer_id.
     *                           ['RETAILER_ID' => ['id', 'vendor_id', ...], ...]
     * @return int[]             IDs de los wa_orders insertados.
     */
    public function createFromOrderMessage(
        string $phone,
        array  $items,
        string $catalogId,
        array  $productMap
    ): array {
        $insertedIds = [];

        $stmt = $this->pdo->prepare(
            'INSERT INTO wa_orders
               (phone_number, catalog_id, product_retailer_id, product_id, vendor_id, quantity, item_price, currency)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        foreach ($items as $item) {
            $retailerId = (string)($item['product_retailer_id'] ?? '');
            $product    = $productMap[$retailerId] ?? null;

            $stmt->execute([
                $phone,
                $catalogId,
                $retailerId,
                $product ? (int)$product['id']        : null,
                $product ? (int)$product['vendor_id'] : null,
                (int)(  $item['quantity']   ?? 1),
                (float)($item['item_price'] ?? 0.00),
                (string)($item['currency']  ?? 'UYU'),
            ]);

            $insertedIds[] = (int)$this->pdo->lastInsertId();
        }

        return $insertedIds;
    }

    /**
     * Registra un pedido de interés simple (sin carrito de WhatsApp).
     * Se usa cuando el bot genera el link al vendedor directamente.
     *
     * @param string $phone      Número del comprador.
     * @param int    $productId  ID del producto en la BD.
     * @param int    $vendorId   ID del vendedor en la BD.
     * @param float  $price      Precio del producto al momento del pedido.
     * @return int               ID del wa_order insertado.
     */
    public function createInterestOrder(
        string $phone,
        int    $productId,
        int    $vendorId,
        float  $price
    ): int {
        $catalogId = defined('WA_CATALOG_ID') ? WA_CATALOG_ID : '';

        $stmt = $this->pdo->prepare(
            'INSERT INTO wa_orders
               (phone_number, catalog_id, product_retailer_id, product_id, vendor_id, quantity, item_price, currency)
             VALUES (?, ?, ?, ?, ?, 1, ?, ?)'
        );
        $stmt->execute([
            $phone,
            $catalogId,
            (string)$productId,  // retailer_id = product.id (igual que en el feed)
            $productId,
            $vendorId,
            $price,
            'UYU',
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Marca un pedido de WhatsApp como contactado.
     */
    public function markAsContacted(int $waOrderId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE wa_orders SET status = ? WHERE id = ?'
        );
        $stmt->execute(['contacted', $waOrderId]);
    }
}
