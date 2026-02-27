<?php
declare(strict_types=1);

namespace TiendaMoroni\Controllers\Admin;

use TiendaMoroni\Core\Session;
use TiendaMoroni\Core\Database as DB;
use TiendaMoroni\Models\ProductModel;
use TiendaMoroni\Models\CategoryModel;
use TiendaMoroni\Models\VendorModel;

class ProductController
{
    public function index(array $params = []): void
    {
        Session::requireAdmin();

        $q          = sanitize(get('q', ''));
        $vendorId   = (int) get('vendor_id', 0) ?: null;
        $categoryId = (int) get('category_id', 0) ?: null;
        $products   = ProductModel::all(50, 0, $q, $vendorId, $categoryId);

        view('admin/products/index', [
            'products'   => $products,
            'q'          => $q,
            'vendorId'   => $vendorId,
            'categoryId' => $categoryId,
            'vendors'    => VendorModel::allForSelect(),
            'categories' => CategoryModel::all(),
            'pageTitle'  => 'Productos – Admin',
        ]);
    }

    public function create(array $params = []): void
    {
        Session::requireAdmin();

        view('admin/products/form', [
            'product'    => null,
            'images'     => [],
            'categories' => CategoryModel::all(),
            'vendors'    => VendorModel::allForSelect(),
            'error'      => Session::getFlash('error'),
            'pageTitle'  => 'Nuevo producto – Admin',
        ]);
    }

    public function store(array $params = []): void
    {
        Session::requireAdmin();
        verifyCsrf();

        $data = $this->collectFormData();

        if ($error = $this->validate($data)) {
            Session::flash('error', $error);
            redirect('/admin/productos/nuevo');
        }

        // Use first pending image as main_image_url when no upload given
        $pendingImages = array_values(array_filter(array_map('trim', $_POST['images'] ?? [])));
        if (empty($data['main_image_url']) && !empty($pendingImages)) {
            $data['main_image_url'] = $pendingImages[0];
        }

        $productId = (int) ProductModel::create($data);

        // Insert pending gallery images into product_images
        foreach ($pendingImages as $order => $url) {
            DB::query(
                'INSERT INTO product_images (product_id, image_url, sort_order) VALUES (?, ?, ?)',
                [$productId, $url, $order]
            );
        }

        redirect('/admin/productos/' . $productId . '/editar?saved=1');
    }

    public function edit(array $params = []): void
    {
        Session::requireAdmin();

        $product = ProductModel::findById((int) ($params['id'] ?? 0));
        if (!$product) {
            redirect('/admin/productos');
        }

        $images = ProductModel::images((int) $product['id']);

        view('admin/products/form', [
            'product'    => $product,
            'images'     => $images,
            'categories' => CategoryModel::all(),
            'vendors'    => VendorModel::allForSelect(),
            'saved'      => get('saved') === '1',
            'error'      => Session::getFlash('error'),
            'pageTitle'  => 'Editar ' . $product['name'] . ' – Admin',
        ]);
    }

    public function update(array $params = []): void
    {
        Session::requireAdmin();
        verifyCsrf();

        $id      = (int) ($params['id'] ?? 0);
        $product = ProductModel::findById($id);
        if (!$product) redirect('/admin/productos');

        $data = $this->collectFormData();

        if ($error = $this->validate($data)) {
            Session::flash('error', $error);
            redirect('/admin/productos/' . $id . '/editar');
        }

        // Handle image upload
        $imageUrl = $this->handleUpload();
        if ($imageUrl) {
            $data['main_image_url'] = $imageUrl;
        } else {
            $data['main_image_url'] = $product['main_image_url'];
        }

        ProductModel::update($id, $data);
        redirect('/admin/productos/' . $id . '/editar?saved=1');
    }

    public function delete(array $params = []): void
    {
        Session::requireAdmin();
        verifyCsrf();

        $id = (int) ($params['id'] ?? 0);
        ProductModel::delete($id);
        redirect('/admin/productos');
    }

    // ── Image management (AJAX) ───────────────────────────────────────────────

    public function addImage(array $params = []): void
    {
        Session::requireAdmin();
        verifyCsrf();

        $productId = (int) ($params['id'] ?? 0);
        $url       = trim(post('url', ''));

        if (!$productId || !$url) {
            jsonResponse(['success' => false, 'message' => 'Datos inválidos.'], 400);
        }

        $product = ProductModel::findById($productId);
        if (!$product) {
            jsonResponse(['success' => false, 'message' => 'Producto no encontrado.'], 404);
        }

        $maxOrder = (int) DB::fetchColumn(
            'SELECT COALESCE(MAX(sort_order), -1) FROM product_images WHERE product_id = ?',
            [$productId]
        );

        DB::query(
            'INSERT INTO product_images (product_id, image_url, sort_order) VALUES (?, ?, ?)',
            [$productId, $url, $maxOrder + 1]
        );
        $imageId = (int) DB::lastInsertId();

        // First image → set as main
        if ($maxOrder === -1) {
            DB::query('UPDATE products SET main_image_url = ? WHERE id = ?', [$url, $productId]);
        }

        jsonResponse([
            'success' => true,
            'image'   => ['id' => $imageId, 'image_url' => $url, 'sort_order' => $maxOrder + 1],
        ]);
    }

    public function removeImage(array $params = []): void
    {
        Session::requireAdmin();
        verifyCsrf();

        $productId      = (int) ($params['id'] ?? 0);
        $imageId        = (int) post('image_id', 0);
        $deleteFromRepo = post('delete_from_repo', '0') === '1';

        $image = DB::fetchOne(
            'SELECT * FROM product_images WHERE id = ? AND product_id = ?',
            [$imageId, $productId]
        );

        if (!$image) {
            jsonResponse(['success' => false, 'message' => 'Imagen no encontrada.'], 404);
        }

        DB::query('DELETE FROM product_images WHERE id = ?', [$imageId]);

        if ($deleteFromRepo) {
            $mediaFile = DB::fetchOne('SELECT * FROM media_files WHERE url = ?', [$image['image_url']]);
            if ($mediaFile) {
                if (file_exists($mediaFile['disk_path'])) {
                    unlink($mediaFile['disk_path']);
                }
                DB::query('DELETE FROM media_files WHERE id = ?', [(int) $mediaFile['id']]);
            }
        }

        // If this was the main image → update to next available
        $product = ProductModel::findById($productId);
        if ($product && $product['main_image_url'] === $image['image_url']) {
            $next = DB::fetchOne(
                'SELECT image_url FROM product_images WHERE product_id = ? ORDER BY sort_order ASC LIMIT 1',
                [$productId]
            );
            DB::query(
                'UPDATE products SET main_image_url = ? WHERE id = ?',
                [$next ? $next['image_url'] : null, $productId]
            );
        }

        jsonResponse(['success' => true]);
    }

    public function reorderImages(array $params = []): void
    {
        Session::requireAdmin();
        verifyCsrf();

        $productId = (int) ($params['id'] ?? 0);
        $order     = json_decode(post('order', '[]'), true); // array of image IDs

        if (!is_array($order) || !$productId) {
            jsonResponse(['success' => false, 'message' => 'Datos inválidos.'], 400);
        }

        foreach ($order as $sortOrder => $imageId) {
            DB::query(
                'UPDATE product_images SET sort_order = ? WHERE id = ? AND product_id = ?',
                [(int) $sortOrder, (int) $imageId, $productId]
            );
        }

        // First image becomes main_image_url
        if (!empty($order)) {
            $first = DB::fetchOne(
                'SELECT image_url FROM product_images WHERE id = ? AND product_id = ?',
                [(int) $order[0], $productId]
            );
            if ($first) {
                DB::query('UPDATE products SET main_image_url = ? WHERE id = ?', [$first['image_url'], $productId]);
            }
        }

        jsonResponse(['success' => true]);
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function collectFormData(): array
    {
        $defaultVendor = VendorModel::first();

        return [
            'vendor_id'         => (int) post('vendor_id') ?: ($defaultVendor ? (int) $defaultVendor['id'] : 1),
            'category_id'       => (int) post('category_id') ?: null,
            'name'              => sanitize(post('name', '')),
            'slug'              => sanitize(post('slug', '')) ?: slugify(post('name', '')),
            'description'       => post('description', ''),
            'short_description' => sanitize(post('short_description', '')),
            'price'             => (float) post('price', 0),
            'stock'             => (int) post('stock', 0),
            'status'            => in_array(post('status'), ['active','inactive','draft']) ? post('status') : 'draft',
            'featured'          => post('featured') ? 1 : 0,
            'main_image_url'    => null,
            'meta_title'        => sanitize(post('meta_title', '')),
            'meta_description'  => sanitize(post('meta_description', '')),
        ];
    }

    private function validate(array $data): ?string
    {
        if (!$data['name']) return 'El nombre es obligatorio.';
        if ($data['price'] <= 0) return 'El precio debe ser mayor a 0.';
        return null;
    }

    private function handleUpload(): ?string
    {
        if (empty($_FILES['main_image']['tmp_name'])) {
            return null;
        }

        $file = $_FILES['main_image'];

        if ($file['error'] !== UPLOAD_ERR_OK) return null;
        if ($file['size'] > UPLOAD_MAX_SIZE) return null;

        $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
        $mime    = mime_content_type($file['tmp_name']);
        if (!in_array($mime, $allowed)) return null;

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('prod_', true) . '.' . strtolower($ext);
        $dest     = UPLOAD_PATH . $filename;

        if (!is_dir(UPLOAD_PATH)) {
            mkdir(UPLOAD_PATH, 0755, true);
        }

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            return UPLOAD_URL . $filename;
        }

        return null;
    }
}
