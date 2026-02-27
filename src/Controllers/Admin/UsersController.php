<?php
declare(strict_types=1);

namespace TiendaMoroni\Controllers\Admin;

use TiendaMoroni\Core\Session;
use TiendaMoroni\Models\UserModel;
use TiendaMoroni\Core\Database as DB;

class UsersController
{
    private const PER_PAGE    = 20;
    private const VALID_ROLES = ['admin', 'buyer'];

    // ── List ─────────────────────────────────────────────────────────────────

    public function index(array $params = []): void
    {
        Session::requireAdmin();

        $q    = trim(sanitize(get('q', '')));
        $role = sanitize(get('role', ''));
        $role = in_array($role, self::VALID_ROLES, true) ? $role : '';

        $filters = array_filter(['q' => $q, 'role' => $role]);

        $total    = UserModel::countAll($filters);
        $pager    = paginate($total, self::PER_PAGE);
        $users    = UserModel::findAll($filters, $pager['per_page'], $pager['offset']);

        $totalAll      = UserModel::countAll([]);
        $totalActive   = UserModel::countByActive(1);
        $totalInactive = UserModel::countByActive(0);

        view('admin/users/index', [
            'pageTitle'     => 'Usuarios — Admin | ' . SITE_NAME,
            'users'         => $users,
            'pager'         => $pager,
            'filters'       => ['q' => $q, 'role' => $role],
            'totalAll'      => $totalAll,
            'totalActive'   => $totalActive,
            'totalInactive' => $totalInactive,
            'currentUser'   => Session::user(),
        ]);
    }

    // ── Detail ───────────────────────────────────────────────────────────────

    public function show(array $params = []): void
    {
        Session::requireAdmin();

        $id   = (int) ($params['id'] ?? 0);
        $user = UserModel::findById($id);

        if (!$user) {
            Session::flash('error', 'Usuario no encontrado.');
            redirect('/admin/usuarios');
        }

        $orders = DB::fetchAll(
            'SELECT id, status, total, created_at FROM orders
             WHERE user_id = ? ORDER BY created_at DESC LIMIT 10',
            [$id]
        );

        view('admin/users/show', [
            'pageTitle'   => 'Usuario #' . $id . ' — Admin | ' . SITE_NAME,
            'user'        => $user,
            'orders'      => $orders,
            'currentUser' => Session::user(),
            'flash'       => Session::getFlash('success'),
            'error'       => Session::getFlash('error'),
        ]);
    }

    // ── Edit form ─────────────────────────────────────────────────────────────

    public function edit(array $params = []): void
    {
        Session::requireAdmin();

        $id   = (int) ($params['id'] ?? 0);
        $user = UserModel::findById($id);

        if (!$user) {
            Session::flash('error', 'Usuario no encontrado.');
            redirect('/admin/usuarios');
        }

        view('admin/users/edit', [
            'pageTitle'   => 'Editar usuario — Admin | ' . SITE_NAME,
            'user'        => $user,
            'errors'      => [],
            'old'         => [],
            'currentUser' => Session::user(),
        ]);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(array $params = []): void
    {
        Session::requireAdmin();

        $id   = (int) ($params['id'] ?? 0);
        $user = UserModel::findById($id);

        if (!$user) {
            Session::flash('error', 'Usuario no encontrado.');
            redirect('/admin/usuarios');
        }

        $name = trim(sanitize(post('name', '')));
        $role = sanitize(post('role', ''));

        $errors = [];
        $cu     = Session::user();

        if ($name === '') {
            $errors['name'] = 'El nombre es obligatorio.';
        }

        if (!in_array($role, self::VALID_ROLES, true)) {
            $errors['role'] = 'Rol no válido.';
        }

        // Admin cannot change their own role
        if ((int) ($cu['id'] ?? 0) === $id) {
            $role = $user['role'];
        }

        if ($errors) {
            view('admin/users/edit', [
                'pageTitle'   => 'Editar usuario — Admin | ' . SITE_NAME,
                'user'        => $user,
                'errors'      => $errors,
                'old'         => ['name' => $name, 'role' => $role],
                'currentUser' => $cu,
            ]);
            return;
        }

        UserModel::update($id, ['name' => $name, 'role' => $role]);
        Session::flash('success', 'Usuario actualizado correctamente.');
        redirect('/admin/usuarios/' . $id);
    }

    // ── Toggle status (JSON) ──────────────────────────────────────────────────

    public function toggleStatus(array $params = []): void
    {
        Session::requireAdmin();

        $id  = (int) ($params['id'] ?? 0);
        $cu  = Session::user();

        if ((int) ($cu['id'] ?? 0) === $id) {
            jsonResponse(['success' => false, 'error' => 'No podés desactivar tu propio usuario.'], 403);
        }

        $user = UserModel::findById($id);
        if (!$user) {
            jsonResponse(['success' => false, 'error' => 'Usuario no encontrado.'], 404);
        }

        $newActive = UserModel::toggleActive($id);
        $msg       = $newActive ? 'Usuario activado.' : 'Usuario desactivado.';

        jsonResponse(['success' => true, 'active' => $newActive, 'message' => $msg]);
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function delete(array $params = []): void
    {
        Session::requireAdmin();

        $id  = (int) ($params['id'] ?? 0);
        $cu  = Session::user();

        // Safety: cannot delete self
        if ((int) ($cu['id'] ?? 0) === $id) {
            Session::flash('error', 'No podés eliminar tu propio usuario.');
            redirect('/admin/usuarios/' . $id);
        }

        // Confirm token must match the user ID
        $token = (int) post('confirm_id', '0');
        if ($token !== $id) {
            Session::flash('error', 'Confirmación inválida.');
            redirect('/admin/usuarios/' . $id);
        }

        $user = UserModel::findById($id);
        if (!$user) {
            Session::flash('error', 'Usuario no encontrado.');
            redirect('/admin/usuarios');
        }

        if (UserModel::hasOrders($id)) {
            Session::flash('error', 'Este usuario tiene órdenes asociadas y no puede eliminarse. Podés desactivarlo en su lugar.');
            redirect('/admin/usuarios/' . $id);
        }

        UserModel::delete($id);
        Session::flash('success', 'Usuario eliminado correctamente.');
        redirect('/admin/usuarios');
    }
}
