<?php
declare(strict_types=1);

namespace TiendaMoroni\Controllers\Admin;

use TiendaMoroni\Core\Session;
use TiendaMoroni\Core\Database as DB;
use TiendaMoroni\Models\VendorModel;

class VendorController
{
    public function index(array $params = []): void
    {
        Session::requireAdmin();

        view('admin/vendors/index', [
            'vendors'   => VendorModel::all(),
            'pageTitle' => 'Vendedores – Admin',
        ]);
    }

    public function create(array $params = []): void
    {
        Session::requireAdmin();

        view('admin/vendors/form', [
            'vendor'    => null,
            'error'     => Session::getFlash('error'),
            'pageTitle' => 'Nuevo vendedor – Admin',
        ]);
    }

    public function store(array $params = []): void
    {
        Session::requireAdmin();
        verifyCsrf();

        $data = $this->collectFormData();

        if (!$data['business_name'] || !$data['email']) {
            Session::flash('error', 'El nombre del negocio y el email son obligatorios.');
            redirect('/admin/vendedores/nuevo');
        }

        // Find or create the associated user
        $user = DB::fetchOne('SELECT id FROM users WHERE email = ?', [$data['email']]);
        if ($user) {
            $userId = (int) $user['id'];
            // Ensure the user is marked as vendor
            DB::query('UPDATE users SET role = ? WHERE id = ?', ['vendor', $userId]);
        } else {
            DB::query(
                'INSERT INTO users (name, email, password_hash, auth_provider, role) VALUES (?, ?, ?, ?, ?)',
                [$data['business_name'], $data['email'], password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT), 'own', 'vendor']
            );
            $userId = (int) DB::lastInsertId();
        }

        VendorModel::create(array_merge($data, ['user_id' => $userId]));
        redirect('/admin/vendedores');
    }

    public function edit(array $params = []): void
    {
        Session::requireAdmin();

        $vendor = VendorModel::findById((int) ($params['id'] ?? 0));
        if (!$vendor) redirect('/admin/vendedores');

        view('admin/vendors/form', [
            'vendor'    => $vendor,
            'error'     => Session::getFlash('error'),
            'pageTitle' => 'Editar vendedor – Admin',
        ]);
    }

    public function update(array $params = []): void
    {
        Session::requireAdmin();
        verifyCsrf();

        $id     = (int) ($params['id'] ?? 0);
        $vendor = VendorModel::findById($id);
        if (!$vendor) redirect('/admin/vendedores');

        $data = $this->collectFormData();

        if (!$data['business_name'] || !$data['email']) {
            Session::flash('error', 'El nombre del negocio y el email son obligatorios.');
            redirect('/admin/vendedores/' . $id . '/editar');
        }

        VendorModel::update($id, $data);
        redirect('/admin/vendedores');
    }

    public function delete(array $params = []): void
    {
        Session::requireAdmin();
        verifyCsrf();

        $id = (int) ($params['id'] ?? 0);
        VendorModel::delete($id);
        redirect('/admin/vendedores');
    }

    private function collectFormData(): array
    {
        $name = sanitize(post('business_name', ''));
        return [
            'business_name'        => $name,
            'slug'                 => sanitize(post('slug', '')) ?: slugify($name),
            'business_description' => sanitize(post('business_description', '')),
            'email'                => sanitize(post('email', '')),
            'phone'                => sanitize(post('phone', '')),
            'is_verified'          => post('is_verified') ? 1 : 0,
        ];
    }
}
