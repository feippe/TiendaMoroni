<?php
declare(strict_types=1);

namespace TiendaMoroni\Controllers;

use TiendaMoroni\Core\Cart;
use TiendaMoroni\Models\ProductModel;

class CartController
{
    public function show(array $params = []): void
    {
        view('cart/index', [
            'items'     => Cart::items(),
            'subtotal'  => Cart::subtotal(),
            'pageTitle' => 'Carrito – ' . SITE_NAME,
            'metaDesc'  => 'Revisá los productos en tu carrito de compras.',
            'canonical' => SITE_URL . '/carrito',
        ]);
    }

    public function add(array $params = []): void
    {
        $productId = (int) post('product_id', 0);
        $qty       = max(1, (int) post('qty', 1));

        $product = ProductModel::findById($productId);

        if (!$product || $product['status'] !== 'active') {
            jsonResponse(['success' => false, 'message' => 'Producto no encontrado.'], 404);
        }

        Cart::add(
            $productId,
            (float) $product['price'],
            $product['name'],
            $product['main_image_url'] ?? '',
            $qty
        );

        jsonResponse([
            'success' => true,
            'count'   => Cart::count(),
            'message' => '¡Artesanía agregada al carrito!',
        ]);
    }

    public function update(array $params = []): void
    {
        $productId = (int) post('product_id', 0);
        $qty       = (int) post('qty', 0);

        Cart::update($productId, $qty);

        jsonResponse([
            'success'  => true,
            'count'    => Cart::count(),
            'subtotal' => Cart::subtotal(),
        ]);
    }

    public function remove(array $params = []): void
    {
        $productId = (int) post('product_id', 0);
        Cart::remove($productId);

        jsonResponse([
            'success'  => true,
            'count'    => Cart::count(),
            'subtotal' => Cart::subtotal(),
        ]);
    }
}
