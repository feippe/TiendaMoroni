<?php
declare(strict_types=1);

namespace TiendaMoroni\Controllers;

use TiendaMoroni\Models\VendorModel;
use TiendaMoroni\Models\ProductModel;

class VendorController
{
    /**
     * GET /vendedor/{slug}
     */
    public function show(array $params = []): void
    {
        $slug   = $params['slug'] ?? '';
        $vendor = VendorModel::findBySlug($slug);

        if (!$vendor) {
            http_response_code(404);
            view('errors/404');
            return;
        }

        $perPage    = 12;
        $total      = ProductModel::count(['vendor_id' => $vendor['id']]);
        $pagination = paginate($total, $perPage);
        $products   = ProductModel::list(['vendor_id' => $vendor['id']], $perPage, $pagination['offset']);

        view('vendors/show', [
            'vendor'     => $vendor,
            'products'   => $products,
            'pagination' => $pagination,
            'pageTitle'  => $vendor['business_name'] . ' – ' . SITE_NAME,
            'metaDesc'   => $vendor['business_description'] ?: 'Productos de ' . $vendor['business_name'] . ' en ' . SITE_NAME . '.',
            'canonical'  => SITE_URL . '/vendedor/' . $vendor['slug'],
        ]);
    }
}
