<?php
declare(strict_types=1);

namespace TiendaMoroni\Controllers;

use TiendaMoroni\Core\Cart;
use TiendaMoroni\Core\Session;
use TiendaMoroni\Core\Mailer;
use TiendaMoroni\Core\Database as DB;
use TiendaMoroni\Models\OrderModel;
use TiendaMoroni\Models\VendorModel;
use TiendaMoroni\Models\ProductModel;

class CheckoutController
{
    public function show(array $params = []): void
    {
        Session::requireAuth('/checkout');

        if (Cart::isEmpty()) {
            redirect('/carrito');
        }

        $user      = Session::user();
        $lastOrder = DB::fetchOne(
            'SELECT contact_phone, shipping_address FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 1',
            [(int) $user['user_id']]
        );

        view('checkout/index', [
            'user'      => $user,
            'items'     => Cart::items(),
            'subtotal'  => Cart::subtotal(),
            'lastOrder' => $lastOrder ?: [],
            'error'     => Session::getFlash('error'),
            'pageTitle' => 'Checkout – ' . SITE_NAME,
            'metaDesc'  => 'Completá tu pedido en TiendaMoroni.',
            'canonical' => SITE_URL . '/checkout',
        ]);
    }

    public function process(array $params = []): void
    {
        Session::requireAuth('/checkout');
        verifyCsrf();

        if (Cart::isEmpty()) {
            redirect('/carrito');
        }

        // Validate & collect form data
        $name    = sanitize(post('full_name', ''));
        $phone   = sanitize(post('phone', ''));
        $street  = sanitize(post('street', ''));
        $city    = sanitize(post('city', ''));
        $dept    = sanitize(post('department', ''));
        $notes   = sanitize(post('notes', ''));

        if (!$name || !$phone || !$street || !$city || !$dept) {
            Session::flash('error', 'Por favor completá todos los campos obligatorios.');
            redirect('/checkout');
        }

        $shippingAddress = "$street, $city, $dept";
        $user            = Session::user();
        $items           = Cart::items();
        $subtotal        = Cart::subtotal();
        $total           = $subtotal; // shipping/tax could be added here

        // Determine vendor from first product in cart
        $vendor = null;
        foreach ($items as $productId => $item) {
            $product = ProductModel::findById((int) $productId);
            if ($product) {
                $vendor = VendorModel::findById((int) $product['vendor_id']);
                break;
            }
        }

        // Save order
        DB::beginTransaction();
        try {
            $orderId = OrderModel::create([
                'user_id'          => (int) $user['user_id'],
                'vendor_id'        => $vendor ? (int) $vendor['id'] : null,
                'subtotal'         => $subtotal,
                'total'            => $total,
                'contact_phone'    => $phone,
                'shipping_address' => $shippingAddress,
                'notes'            => $notes,
            ]);

            foreach ($items as $productId => $item) {
                OrderModel::addItem($orderId, [
                    'product_id' => (int) $productId,
                    'quantity'   => (int) $item['qty'],
                    'unit_price' => (float) $item['price'],
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollback();
            if (APP_DEBUG) error_log($e->getMessage());
            Session::flash('error', 'Ocurrió un error al procesar tu pedido. Intentá de nuevo.');
            redirect('/checkout');
        }

        // Reduce stock for each ordered product
        foreach ($items as $productId => $item) {
            DB::query(
                'UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ?',
                [(int) $item['qty'], (int) $productId]
            );
        }

        Cart::clear();

        // Send emails
        $this->sendOrderEmails($orderId, $user, $items, $total, $shippingAddress, $phone, $notes, $vendor);

        redirect('/checkout/confirmacion?orden=' . $orderId);
    }

    public function confirmation(array $params = []): void
    {
        Session::requireAuth('/checkout');

        $orderId = (int) get('orden', 0);
        $order   = $orderId ? OrderModel::findById($orderId) : null;

        if (!$order || (int) $order['user_id'] !== (int) Session::user()['user_id']) {
            redirect('/mi-cuenta');
        }

        $items = OrderModel::items($orderId);

        view('checkout/confirmation', [
            'order'     => $order,
            'items'     => $items,
            'pageTitle' => 'Pedido #' . $orderId . ' confirmado – ' . SITE_NAME,
            'metaDesc'  => 'Tu pedido fue recibido exitosamente.',
            'canonical' => SITE_URL . '/checkout/confirmacion?orden=' . $orderId,
        ]);
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function sendOrderEmails(
        int    $orderId,
        array  $user,
        array  $items,
        float  $total,
        string $shippingAddress,
        string $phone,
        string $notes,
        ?array $vendor
    ): void {
        $itemsHtml = '';
        foreach ($items as $productId => $item) {
            $itemsHtml .= '<tr>
                <td style="padding:6px 0; border-bottom:1px solid #eee;">' . e($item['name']) . '</td>
                <td style="padding:6px 0; border-bottom:1px solid #eee; text-align:center;">' . (int) $item['qty'] . '</td>
                <td style="padding:6px 0; border-bottom:1px solid #eee; text-align:right;">' . formatPrice($item['price']) . '</td>
                <td style="padding:6px 0; border-bottom:1px solid #eee; text-align:right;">' . formatPrice($item['price'] * $item['qty']) . '</td>
            </tr>';
        }

        $html = "
        <div style='font-family:Georgia,serif; max-width:600px; margin:0 auto; background:#FAFAF7; padding:32px; border-radius:12px;'>
            <h2 style='color:#1B3A5C; font-size:22px;'>Nuevo pedido #{$orderId} en " . SITE_NAME . "</h2>
            <p style='color:#444; margin:4px 0;'><strong>Comprador:</strong> {$user['name']} ({$user['email']})</p>
            <p style='color:#444; margin:4px 0;'><strong>Teléfono:</strong> {$phone}</p>
            <p style='color:#444; margin:4px 0;'><strong>Dirección:</strong> {$shippingAddress}</p>
            " . ($notes ? "<p style='color:#444; margin:4px 0;'><strong>Notas:</strong> {$notes}</p>" : '') . "
            <table style='width:100%; border-collapse:collapse; margin-top:20px;'>
                <thead>
                    <tr style='background:#f5f5f5;'>
                        <th style='padding:8px; text-align:left;'>Producto</th>
                        <th style='padding:8px; text-align:center;'>Cant.</th>
                        <th style='padding:8px; text-align:right;'>Precio unit.</th>
                        <th style='padding:8px; text-align:right;'>Subtotal</th>
                    </tr>
                </thead>
                <tbody>{$itemsHtml}</tbody>
                <tfoot>
                    <tr>
                        <td colspan='3' style='padding:8px; text-align:right; font-weight:bold;'>TOTAL</td>
                        <td style='padding:8px; text-align:right; font-weight:bold;'>" . formatPrice($total) . "</td>
                    </tr>
                </tfoot>
            </table>
            <p style='margin-top:24px; color:#888; font-size:13px;'>Este email fue generado automáticamente por " . SITE_NAME . " &mdash; el marketplace de nuestra comunidad.</p>
        </div>";

        // To admin
        Mailer::send(
            ADMIN_EMAIL,
            'Admin TiendaMoroni',
            "Nuevo pedido #{$orderId} – " . SITE_NAME,
            $html
        );

        // To vendor
        if ($vendor) {
            Mailer::send(
                $vendor['email'],
                $vendor['business_name'],
                "Nuevo pedido #{$orderId} – " . SITE_NAME,
                $html
            );
        }

        // Confirmation to buyer
        $buyerHtml = "
        <div style='font-family:Georgia,serif; max-width:600px; margin:0 auto; background:#FAFAF7; padding:32px; border-radius:12px;'>
            <h2 style='color:#1B3A5C; font-size:22px;'>¡Gracias por tu compra, {$user['name']}!</h2>
            <p style='color:#555;'>Recibimos tu pedido <strong>#{$orderId}</strong> con éxito. El artesano se pondrá en contacto con vos muy pronto para coordinar el envío.</p>
            <p style='color:#555; font-style:italic;'>Gracias por apoyar a los artesanos de nuestra comunidad. 🕊️</p>
            $html
        </div>";

        Mailer::send(
            $user['email'],
            $user['name'],
            "Confirmación de pedido #{$orderId} – " . SITE_NAME,
            $buyerHtml
        );
    }
}
