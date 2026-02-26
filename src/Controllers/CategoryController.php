<?php
declare(strict_types=1);

namespace TiendaMoroni\Controllers;

use TiendaMoroni\Models\CategoryModel;
use TiendaMoroni\Models\ProductModel;

class CategoryController
{
    /**
     * GET /categoria/{slug}
     */
    public function show(array $params = []): void
    {
        $slug     = $params['slug'] ?? '';
        $category = CategoryModel::findBySlug($slug);

        if (!$category) {
            http_response_code(404);
            view('errors/404');
            return;
        }

        $perPage    = 12;
        $total      = ProductModel::count(['category_id' => $category['id']]);
        $pagination = paginate($total, $perPage);
        $products   = ProductModel::byCategory((int) $category['id'], $perPage, $pagination['offset']);

        $parentCategory = $category['parent_id']
            ? CategoryModel::findById((int) $category['parent_id'])
            : null;

        view('categories/show', [
            'category'       => $category,
            'parentCategory' => $parentCategory,
            'products'       => $products,
            'pagination'     => $pagination,
            'pageTitle'      => ($category['meta_title'] ?: $category['name']) . ' – ' . SITE_NAME,
            'metaDesc'       => $category['meta_description'] ?: $category['description'],
            'canonical'      => SITE_URL . '/categoria/' . $category['slug'],
        ]);
    }
}
