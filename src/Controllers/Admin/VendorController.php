<?php
declare(strict_types=1);

namespace TiendaMoroni\Controllers\Admin;

use TiendaMoroni\Core\Session;
use TiendaMoroni\Models\VendorModel;

class VendorController
{
    public function index(array $params = []): void
    {
        Session::requireAdmin();

        $vendors = VendorModel::all();

        view('admin/vendors/index', [
            'vendors'   => $vendors,
            'pageTitle' => 'Vendedores – Admin',
        ]);
    }
}
