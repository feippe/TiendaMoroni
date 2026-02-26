<?php
declare(strict_types=1);

namespace TiendaMoroni\Controllers;

use TiendaMoroni\Models\ProductModel;

class ApiController
{
    /**
     * GET /api/products?q=&categoria=&min_price=&max_price=&orden=&page=
     */
    public function products(array $params = []): void
    {
        $filters = [
            'q'           => sanitize(get('q', '')),
            'category_id' => (int) get('categoria', 0) ?: null,
            'min_price'   => get('min_price', ''),
            'max_price'   => get('max_price', ''),
            'sort'        => get('orden', ''),
        ];

        $perPage    = 12;
        $total      = ProductModel::count($filters);
        $pagination = paginate($total, $perPage);
        $products   = ProductModel::list($filters, $perPage, $pagination['offset']);

        // Sanitize output for JSON
        $out = array_map(function (array $p) {
            return [
                'id'                => $p['id'],
                'name'              => $p['name'],
                'slug'              => $p['slug'],
                'price'             => $p['price'],
                'price_formatted'   => formatPrice((float) $p['price']),
                'short_description' => $p['short_description'],
                'image'             => $p['main_image_url'],
                'category'          => $p['category_name'] ?? null,
                'url'               => SITE_URL . '/producto/' . $p['slug'],
            ];
        }, $products);

        jsonResponse([
            'products'   => $out,
            'pagination' => $pagination,
        ]);
    }
}
