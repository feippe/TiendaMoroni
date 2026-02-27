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

        // Include products from all sub-categories recursively
        $categoryIds = CategoryModel::descendantIds((int) $category['id']);

        $perPage    = 12;
        $total      = ProductModel::count(['category_ids' => $categoryIds]);
        $pagination = paginate($total, $perPage);
        $products   = ProductModel::list(['category_ids' => $categoryIds], $perPage, $pagination['offset']);

        $parentCategory = $category['parent_id']
            ? CategoryModel::findById((int) $category['parent_id'])
            : null;

        $subcategories = CategoryModel::children((int) $category['id']);

        view('categories/show', [
            'category'       => $category,
            'parentCategory' => $parentCategory,
            'subcategories'  => $subcategories,
            'products'       => $products,
            'pagination'     => $pagination,
            'pageTitle'      => ($category['meta_title'] ?: $category['name']) . ' – ' . SITE_NAME,
            'metaDesc'       => $category['meta_description'] ?: $category['description'],
            'canonical'      => SITE_URL . '/categoria/' . $category['slug'],
        ]);
    }
}
