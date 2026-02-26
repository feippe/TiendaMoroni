<?php
declare(strict_types=1);

namespace TiendaMoroni\Controllers;

use TiendaMoroni\Models\ProductModel;
use TiendaMoroni\Models\CategoryModel;

class HomeController
{
    public function index(array $params = []): void
    {
        $featured   = ProductModel::featured(8);
        $recent     = ProductModel::recent(4);
        $categories = CategoryModel::withProductCount();

        view('home/index', [
            'featured'   => $featured,
            'recent'     => $recent,
            'categories' => $categories,
            'pageTitle'  => SITE_NAME . ' – Mercado online',
            'metaDesc'   => 'TiendaMoroni es tu marketplace para encontrar los mejores productos al mejor precio.',
            'canonical'  => SITE_URL . '/',
        ]);
    }
}
