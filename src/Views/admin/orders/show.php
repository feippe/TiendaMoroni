<?php $layout = 'layout/admin'; ?>

<?php
$statusColors = [
  'pending'   => 'bg-yellow-100 text-yellow-800',
  'confirmed' => 'bg-blue-100 text-blue-800',
  'shipped'   => 'bg-indigo-100 text-indigo-800',
  'delivered' => 'bg-green-100 text-green-800',
  'cancelled' => 'bg-red-100 text-red-800',
];
$statusLabels = [
  'pending'   => 'Pendiente',
  'confirmed' => 'Confirmado',
  'shipped'   => 'Enviado',
  'delivered' => 'Entregado',
  'cancelled' => 'Cancelado',
];
?>

<div class="mb-6 flex items-center gap-3">
  <a href="/admin/pedidos" class="text-warm-500 hover:text-warm-900 transition text-sm">← Pedidos</a>
  <h2 class="text-xl font-bold text-warm-900">Pedido #<?= (int)$order['id'] ?></h2>
  <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $statusColors[$order['status']] ?? '' ?>">
    <?= $statusLabels[$order['status']] ?? $order['status'] ?>
  </span>
</div>

<?php if (!empty($flash)): ?>
<div class="mb-4 bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm"><?= e($flash) ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  <!-- Items -->
  <div class="lg:col-span-2 space-y-5">
    <div class="bg-white rounded-2xl border border-warm-200 overflow-hidden">
      <div class="px-5 py-4 border-b border-warm-100 font-semibold text-warm-900">Artículos</div>
      <table class="min-w-full text-sm">
        <thead class="bg-warm-50 text-warm-600 uppercase text-xs tracking-wider">
          <tr>
            <th class="px-5 py-2 text-left">Producto</th>
            <th class="px-5 py-2 text-right">Precio</th>
            <th class="px-5 py-2 text-right">Cant.</th>
            <th class="px-5 py-2 text-right">Subtotal</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-warm-100">
          <?php foreach ($items as $item): ?>
          <tr>
            <td class="px-5 py-3 text-warm-900"><?= e($item['product_name']) ?></td>
            <td class="px-5 py-3 text-right text-warm-600"><?= formatPrice($item['unit_price']) ?></td>
            <td class="px-5 py-3 text-right text-warm-600"><?= (int)$item['quantity'] ?></td>
            <td class="px-5 py-3 text-right font-semibold text-warm-900"><?= formatPrice($item['unit_price'] * $item['quantity']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="bg-warm-50">
          <tr>
            <td colspan="3" class="px-5 py-3 text-right font-bold text-warm-900">Total</td>
            <td class="px-5 py-3 text-right font-bold text-lg text-brand-800"><?= formatPrice($order['total']) ?></td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- Contact & shipping -->
    <div class="bg-white rounded-2xl border border-warm-200 p-5">
      <h3 class="font-semibold text-warm-900 mb-3">Datos de entrega</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
        <div>
          <p class="text-xs text-warm-500 uppercase tracking-wider font-medium mb-0.5">Comprador</p>
          <p class="text-warm-800 font-medium"><?= e($order['buyer_name']) ?></p>
          <p class="text-warm-500 text-xs mt-0.5"><?= e($order['buyer_email']) ?></p>
        </div>
        <div>
          <p class="text-xs text-warm-500 uppercase tracking-wider font-medium mb-0.5">Teléfono</p>
          <p class="text-warm-800 font-medium"><?= e($order['contact_phone']) ?></p>
        </div>
        <div class="sm:col-span-2">
          <p class="text-xs text-warm-500 uppercase tracking-wider font-medium mb-0.5">Dirección de envío</p>
          <p class="text-warm-800"><?= e($order['shipping_address']) ?></p>
        </div>
        <?php if (!empty($order['notes'])): ?>
        <div class="sm:col-span-2">
          <p class="text-xs text-warm-500 uppercase tracking-wider font-medium mb-0.5">Notas del pedido</p>
          <p class="text-warm-600 italic"><?= e($order['notes']) ?></p>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Sidebar: Update status + order meta -->
  <div class="space-y-5">
    <div class="bg-white rounded-2xl border border-warm-200 p-5">
      <h3 class="font-semibold text-warm-900 mb-3">Actualizar estado</h3>
      <form method="post" action="/admin/pedidos/<?= (int)$order['id'] ?>/estado" class="space-y-3">
        <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
        <select name="status"
                class="w-full border border-warm-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
          <?php foreach ($statusLabels as $val => $label): ?>
          <option value="<?= $val ?>" <?= $order['status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit"
                class="w-full bg-brand-800 text-white py-2.5 rounded-xl text-sm font-bold hover:bg-brand-700 transition">
          Guardar estado
        </button>
      </form>
    </div>

    <div class="bg-white rounded-2xl border border-warm-200 p-5 space-y-2 text-sm">
      <h3 class="font-semibold text-warm-900 mb-1">Detalles</h3>
      <div class="flex justify-between text-warm-600">
        <span>Fecha</span>
        <span><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></span>
      </div>
      <div class="flex justify-between text-warm-600">
        <span>Cliente</span>
        <span class="truncate ml-2 text-right"><?= e($order['buyer_email']) ?></span>
      </div>
      <div class="flex justify-between text-warm-600">
        <span>Subtotal</span>
        <span><?= formatPrice($order['subtotal']) ?></span>
      </div>
      <div class="flex justify-between font-bold text-warm-900 border-t border-warm-100 pt-2 mt-2">
        <span>Total</span>
        <span><?= formatPrice($order['total']) ?></span>
      </div>
    </div>
  </div>

</div>
