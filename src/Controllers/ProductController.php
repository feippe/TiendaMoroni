<?php
declare(strict_types=1);

namespace TiendaMoroni\Controllers;

use TiendaMoroni\Models\ProductModel;
use TiendaMoroni\Models\CategoryModel;
use TiendaMoroni\Models\QuestionModel;
use TiendaMoroni\Core\Session;

class ProductController
{
    /**
     * GET /productos
     */
    public function index(array $params = []): void
    {
        $filters = [
            'q'           => sanitize(get('q', '')),
            'category_id' => (int) get('categoria', 0) ?: null,
            'vendor_id'   => (int) get('vendedor', 0) ?: null,
            'min_price'   => get('precio_min', ''),
            'max_price'   => get('precio_max', ''),
            'sort'        => get('orden', ''),
        ];

        // Expand selected category to include all descendants
        if ($filters['category_id']) {
            $filters['category_ids'] = CategoryModel::descendantIds((int) $filters['category_id']);
        }

        $perPage    = 12;
        $total      = ProductModel::count($filters);
        $pagination = paginate($total, $perPage);
        $products   = ProductModel::list($filters, $perPage, $pagination['offset']);
        $categories = CategoryModel::tree();

        view('products/index', [
            'products'   => $products,
            'categories' => $categories,
            'filters'    => $filters,
            'pagination' => $pagination,
            'pageTitle'  => 'Productos – ' . SITE_NAME,
            'metaDesc'   => 'Explorá todos nuestros productos y encontrá lo que necesitás.',
            'canonical'  => SITE_URL . '/productos',
        ]);
    }

    /**
     * GET /producto/{slug}
     */
    public function show(array $params = []): void
    {
        $slug    = $params['slug'] ?? '';
        $product = ProductModel::findBySlug($slug);

        if (!$product || $product['status'] !== 'active') {
            http_response_code(404);
            view('errors/404');
            return;
        }

        $images    = ProductModel::images((int) $product['id']);
        $questions = QuestionModel::byProduct((int) $product['id'], publicOnly: true);

        // Handle question submission
        $questionSaved = false;
        $questionError = null;

        if (isPost() && isset($_POST['ask_question'])) {
            if (!Session::isLoggedIn()) {
                redirect('/auth/login?redirect=' . urlencode('/producto/' . $slug));
            }

            verifyCsrf();
            $questionText = sanitize(post('question', ''));

            if (strlen($questionText) < 5) {
                $questionError = 'La pregunta debe tener al menos 5 caracteres.';
            } else {
                QuestionModel::create(
                    (int) $product['id'],
                    (int) Session::user()['user_id'],
                    $questionText
                );
                $questionSaved = true;
                $questions = QuestionModel::byProduct((int) $product['id'], publicOnly: true);
            }
        }

        $category = $product['category_id']
            ? CategoryModel::findById((int) $product['category_id'])
            : null;

        $vendorProducts = ProductModel::byVendor(
            (int) $product['vendor_id'],
            (int) $product['id'],
            $product['category_id'] ? (int) $product['category_id'] : null
        );

        view('products/show', [
            'product'        => $product,
            'images'         => $images,
            'questions'      => $questions,
            'questionSaved'  => $questionSaved,
            'questionError'  => $questionError,
            'category'       => $category,
            'vendorProducts' => $vendorProducts,
            'pageTitle'     => ($product['meta_title'] ?: $product['name']) . ' – ' . SITE_NAME,
            'metaDesc'      => $product['meta_description'] ?: $product['short_description'],
            'canonical'     => SITE_URL . '/producto/' . $product['slug'],
            'ogImage'       => $product['main_image_url'],
        ]);
    }

    /**
     * GET /buscar?q=...
     */
    public function search(array $params = []): void
    {
        $q = sanitize(get('q', ''));

        $filters = ['q' => $q];

        $perPage    = 12;
        $total      = ProductModel::count($filters);
        $pagination = paginate($total, $perPage);
        $products   = ProductModel::list($filters, $perPage, $pagination['offset']);

        view('products/search', [
            'q'          => $q,
            'products'   => $products,
            'pagination' => $pagination,
            'pageTitle'  => 'Buscar: "' . e($q) . '" – ' . SITE_NAME,
            'metaDesc'   => 'Resultados de búsqueda para "' . $q . '" en TiendaMoroni.',
            'canonical'  => SITE_URL . '/buscar?q=' . urlencode($q),
        ]);
    }
}
