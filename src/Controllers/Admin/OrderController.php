<?php
declare(strict_types=1);

namespace TiendaMoroni\Controllers\Admin;

use TiendaMoroni\Core\Session;
use TiendaMoroni\Models\OrderModel;

class OrderController
{
    public function index(array $params = []): void
    {
        Session::requireAdmin();

        $status = sanitize(get('status', ''));
        $orders = OrderModel::all(50, 0, $status);

        view('admin/orders/index', [
            'orders'    => $orders,
            'status'    => $status,
            'pageTitle' => 'Pedidos – Admin',
        ]);
    }

    public function show(array $params = []): void
    {
        Session::requireAdmin();

        $id    = (int) ($params['id'] ?? 0);
        $order = OrderModel::findById($id);

        if (!$order) redirect('/admin/ordenes');

        $items = OrderModel::items($id);

        view('admin/orders/show', [
            'order'     => $order,
            'items'     => $items,
            'flash'     => Session::getFlash('orderSaved'),
            'pageTitle' => 'Orden #' . $id . ' – Admin',
        ]);
    }

    public function updateStatus(array $params = []): void
    {
        Session::requireAdmin();
        verifyCsrf();

        $id     = (int) ($params['id'] ?? 0);
        $status = sanitize(post('status', ''));

        $allowed = ['pending','confirmed','shipped','delivered','cancelled'];
        if (!in_array($status, $allowed)) {
            redirect('/admin/pedidos/' . $id);
        }

        OrderModel::updateStatus($id, $status);
        Session::flash('orderSaved', 'Estado actualizado correctamente.');
        redirect('/admin/pedidos/' . $id);
    }
}
