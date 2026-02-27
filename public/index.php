<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Bootstrap
|--------------------------------------------------------------------------
*/
define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/config/config.php';
require BASE_PATH . '/src/Core/helpers.php';

/*
|--------------------------------------------------------------------------
| PSR-4 style autoloader  (TiendaMoroni\ → src/)
|--------------------------------------------------------------------------
*/
spl_autoload_register(function (string $class): void {
    $prefix   = 'TiendaMoroni\\';
    $baseDir  = BASE_PATH . '/src/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file     = $baseDir . str_replace('\\', '/', $relative) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

/*
|--------------------------------------------------------------------------
| Session
|--------------------------------------------------------------------------
*/
session_start();

use TiendaMoroni\Core\Session;
use TiendaMoroni\Core\Router;

Session::start();

/*
|--------------------------------------------------------------------------
| Middleware
|--------------------------------------------------------------------------
*/
use TiendaMoroni\Core\MaintenanceMiddleware;
MaintenanceMiddleware::handle();

/*
|--------------------------------------------------------------------------
| Routes
|--------------------------------------------------------------------------
*/
$router = new Router();

// ── Public ──────────────────────────────────────────────────────────────

$router->get('/',                    ['TiendaMoroni\Controllers\HomeController',     'index']);

// Products
$router->get('/productos',           ['TiendaMoroni\Controllers\ProductController',  'index']);
$router->get('/producto/{slug}',     ['TiendaMoroni\Controllers\ProductController',  'show']);
$router->post('/producto/{slug}',    ['TiendaMoroni\Controllers\ProductController',  'show']);
$router->get('/buscar',              ['TiendaMoroni\Controllers\ProductController',  'search']);

// Categories
$router->get('/categoria/{slug}',    ['TiendaMoroni\Controllers\CategoryController', 'show']);

// Vendors
$router->get('/vendedor/{slug}',     ['TiendaMoroni\Controllers\VendorController',  'show']);

// Publicar gratis (artisan landing)
$router->get('/publicar-gratis',     ['TiendaMoroni\Controllers\PublishController', 'show']);
$router->post('/publicar-gratis',    ['TiendaMoroni\Controllers\PublishController', 'store']);

// Cart
$router->get('/carrito',             ['TiendaMoroni\Controllers\CartController',     'show']);
$router->post('/carrito/agregar',    ['TiendaMoroni\Controllers\CartController',     'add']);
$router->post('/carrito/actualizar', ['TiendaMoroni\Controllers\CartController',     'update']);
$router->post('/carrito/eliminar',   ['TiendaMoroni\Controllers\CartController',     'remove']);

// Checkout
$router->get('/checkout',            ['TiendaMoroni\Controllers\CheckoutController', 'show']);
$router->post('/checkout/procesar',  ['TiendaMoroni\Controllers\CheckoutController', 'process']);
$router->get('/checkout/confirmacion', ['TiendaMoroni\Controllers\CheckoutController', 'confirmation']);

// Auth
$router->get('/mi-cuenta',           ['TiendaMoroni\Controllers\AuthController', 'account']);
$router->get('/auth/login',          ['TiendaMoroni\Controllers\AuthController', 'loginForm']);
$router->post('/auth/login',         ['TiendaMoroni\Controllers\AuthController', 'loginPost']);
$router->get('/auth/logout',         ['TiendaMoroni\Controllers\AuthController', 'logout']);
$router->get('/auth/register',       ['TiendaMoroni\Controllers\AuthController', 'registerForm']);
$router->post('/auth/register',      ['TiendaMoroni\Controllers\AuthController', 'registerPost']);
$router->get('/auth/google',         ['TiendaMoroni\Controllers\AuthController', 'googleRedirect']);
$router->get('/auth/google/callback',['TiendaMoroni\Controllers\AuthController', 'googleCallback']);

// API
$router->get('/api/products',        ['TiendaMoroni\Controllers\ApiController', 'products']);

// ── Admin ────────────────────────────────────────────────────────────────

$router->get('/admin/login',         ['TiendaMoroni\Controllers\AuthController', 'loginForm']);
$router->post('/admin/login',        ['TiendaMoroni\Controllers\AuthController', 'loginPost']);
$router->get('/admin',               ['TiendaMoroni\Controllers\Admin\DashboardController', 'index']);

// Admin — Products
$router->get('/admin/productos',              ['TiendaMoroni\Controllers\Admin\ProductController', 'index']);
$router->get('/admin/productos/nuevo',        ['TiendaMoroni\Controllers\Admin\ProductController', 'create']);
$router->post('/admin/productos/guardar',     ['TiendaMoroni\Controllers\Admin\ProductController', 'store']);
$router->get('/admin/productos/{id}/editar',  ['TiendaMoroni\Controllers\Admin\ProductController', 'edit']);
$router->post('/admin/productos/{id}/actualizar', ['TiendaMoroni\Controllers\Admin\ProductController', 'update']);
$router->post('/admin/productos/{id}/eliminar',   ['TiendaMoroni\Controllers\Admin\ProductController', 'delete']);

// Admin — Categories
$router->get('/admin/categorias',               ['TiendaMoroni\Controllers\Admin\CategoryController', 'index']);
$router->get('/admin/categorias/nueva',         ['TiendaMoroni\Controllers\Admin\CategoryController', 'create']);
$router->post('/admin/categorias/guardar',      ['TiendaMoroni\Controllers\Admin\CategoryController', 'store']);
$router->get('/admin/categorias/{id}/editar',   ['TiendaMoroni\Controllers\Admin\CategoryController', 'edit']);
$router->post('/admin/categorias/{id}/actualizar', ['TiendaMoroni\Controllers\Admin\CategoryController', 'update']);
$router->post('/admin/categorias/{id}/eliminar',   ['TiendaMoroni\Controllers\Admin\CategoryController', 'delete']);

// Admin — Orders
$router->get('/admin/pedidos',             ['TiendaMoroni\Controllers\Admin\OrderController', 'index']);
$router->get('/admin/pedidos/{id}',        ['TiendaMoroni\Controllers\Admin\OrderController', 'show']);
$router->post('/admin/pedidos/{id}/estado',['TiendaMoroni\Controllers\Admin\OrderController', 'updateStatus']);

// Admin — Questions
$router->get('/admin/preguntas',                     ['TiendaMoroni\Controllers\Admin\QuestionController', 'index']);
$router->post('/admin/preguntas/{id}/responder',     ['TiendaMoroni\Controllers\Admin\QuestionController', 'answer']);
$router->post('/admin/preguntas/{id}/eliminar',      ['TiendaMoroni\Controllers\Admin\QuestionController', 'delete']);

// Admin — Vendors
$router->get('/admin/vendedores',                          ['TiendaMoroni\Controllers\Admin\VendorController', 'index']);
$router->get('/admin/vendedores/nuevo',                    ['TiendaMoroni\Controllers\Admin\VendorController', 'create']);
$router->post('/admin/vendedores/guardar',                 ['TiendaMoroni\Controllers\Admin\VendorController', 'store']);
$router->get('/admin/vendedores/{id}/editar',              ['TiendaMoroni\Controllers\Admin\VendorController', 'edit']);
$router->post('/admin/vendedores/{id}/actualizar',         ['TiendaMoroni\Controllers\Admin\VendorController', 'update']);
$router->post('/admin/vendedores/{id}/eliminar',           ['TiendaMoroni\Controllers\Admin\VendorController', 'delete']);

// Admin — Users
$router->get('/admin/usuarios',                          ['TiendaMoroni\Controllers\Admin\UsersController', 'index']);
$router->get('/admin/usuarios/{id}',                     ['TiendaMoroni\Controllers\Admin\UsersController', 'show']);
$router->get('/admin/usuarios/{id}/editar',              ['TiendaMoroni\Controllers\Admin\UsersController', 'edit']);
$router->post('/admin/usuarios/{id}/editar',             ['TiendaMoroni\Controllers\Admin\UsersController', 'update']);
$router->post('/admin/usuarios/{id}/toggle-status',      ['TiendaMoroni\Controllers\Admin\UsersController', 'toggleStatus']);
$router->post('/admin/usuarios/{id}/eliminar',           ['TiendaMoroni\Controllers\Admin\UsersController', 'delete']);

// Admin — Settings
$router->get('/admin/configuracion',         ['TiendaMoroni\Controllers\Admin\SettingsController', 'index']);
$router->post('/admin/configuracion/toggle', ['TiendaMoroni\Controllers\Admin\SettingsController', 'toggle']);

// Admin — Media library
$router->get('/admin/media',              ['TiendaMoroni\Controllers\Admin\MediaController', 'index']);
$router->post('/admin/media/subir',       ['TiendaMoroni\Controllers\Admin\MediaController', 'upload']);
$router->post('/admin/media/carpeta',     ['TiendaMoroni\Controllers\Admin\MediaController', 'createFolder']);
$router->post('/admin/media/eliminar',    ['TiendaMoroni\Controllers\Admin\MediaController', 'deleteFile']);

// Admin — Product image management
$router->post('/admin/productos/{id}/imagenes/agregar', ['TiendaMoroni\Controllers\Admin\ProductController', 'addImage']);
$router->post('/admin/productos/{id}/imagenes/quitar',  ['TiendaMoroni\Controllers\Admin\ProductController', 'removeImage']);
$router->post('/admin/productos/{id}/imagenes/orden',   ['TiendaMoroni\Controllers\Admin\ProductController', 'reorderImages']);

/*
|--------------------------------------------------------------------------
| Dispatch
|--------------------------------------------------------------------------
*/
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
