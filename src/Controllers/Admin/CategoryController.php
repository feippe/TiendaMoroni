<?php
declare(strict_types=1);

namespace TiendaMoroni\Controllers\Admin;

use TiendaMoroni\Core\Session;
use TiendaMoroni\Models\CategoryModel;

class CategoryController
{
    public function index(array $params = []): void
    {
        Session::requireAdmin();

        view('admin/categories/index', [
            'categories' => CategoryModel::all(),
            'pageTitle'  => 'Categorías – Admin',
        ]);
    }

    public function create(array $params = []): void
    {
        Session::requireAdmin();

        view('admin/categories/form', [
            'category'   => null,
            'categories' => CategoryModel::all(),
            'error'      => Session::getFlash('error'),
            'pageTitle'  => 'Nueva categoría – Admin',
        ]);
    }

    public function store(array $params = []): void
    {
        Session::requireAdmin();
        verifyCsrf();

        $data = $this->collectFormData();

        if (!$data['name']) {
            Session::flash('error', 'El nombre es obligatorio.');
            redirect('/admin/categorias/nueva');
        }

        CategoryModel::create($data);
        redirect('/admin/categorias');
    }

    public function edit(array $params = []): void
    {
        Session::requireAdmin();

        $category = CategoryModel::findById((int) ($params['id'] ?? 0));
        if (!$category) redirect('/admin/categorias');

        view('admin/categories/form', [
            'category'   => $category,
            'categories' => CategoryModel::all(),
            'error'      => Session::getFlash('error'),
            'pageTitle'  => 'Editar categoría – Admin',
        ]);
    }

    public function update(array $params = []): void
    {
        Session::requireAdmin();
        verifyCsrf();

        $id = (int) ($params['id'] ?? 0);
        $data = $this->collectFormData();

        if (!$data['name']) {
            Session::flash('error', 'El nombre es obligatorio.');
            redirect('/admin/categorias/' . $id . '/editar');
        }

        CategoryModel::update($id, $data);
        redirect('/admin/categorias');
    }

    public function delete(array $params = []): void
    {
        Session::requireAdmin();
        verifyCsrf();

        $id = (int) ($params['id'] ?? 0);
        CategoryModel::delete($id);
        redirect('/admin/categorias');
    }

    private function collectFormData(): array
    {
        $name = sanitize(post('name', ''));
        return [
            'name'             => $name,
            'slug'             => sanitize(post('slug', '')) ?: slugify($name),
            'description'      => sanitize(post('description', '')),
            'image_url'        => sanitize(post('image_url', '')),
            'parent_id'        => (int) post('parent_id') ?: null,
            'meta_title'       => sanitize(post('meta_title', '')),
            'meta_description' => sanitize(post('meta_description', '')),
            'sort_order'       => (int) post('sort_order', 0),
        ];
    }
}
