<?php
declare(strict_types=1);

namespace TiendaMoroni\Controllers\Admin;

use TiendaMoroni\Core\Session;
use TiendaMoroni\Models\OrderModel;
use TiendaMoroni\Models\QuestionModel;

class DashboardController
{
    public function index(array $params = []): void
    {
        Session::requireAdmin();

        $stats         = OrderModel::stats();
        $recentOrders  = OrderModel::all(10);
        $pendingQA     = QuestionModel::unansweredCount();

        view('admin/dashboard', [
            'stats'        => $stats,
            'recentOrders' => $recentOrders,
            'pendingQA'    => $pendingQA,
            'pageTitle'    => 'Dashboard – Admin ' . SITE_NAME,
        ]);
    }
}
