<?php $layout = 'layout/app'; ?>

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
  <!-- Success icon -->
  <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
    </svg>
  </div>

  <h1 class="text-3xl font-bold text-warm-900 mb-2 font-serif" style="font-family:'Playfair Display',Georgia,serif">¡Pedido confirmado!</h1>
  <p class="text-warm-500 mb-1">Orden <strong class="text-warm-900">#<?= (int)$order['id'] ?></strong></p>
  <p class="text-warm-500 text-sm mb-1">
    Enviamos una confirmación a <strong><?= e($order['buyer_email']) ?></strong>.
    El artesano se pondrá en contacto para coordinar la entrega.
  </p>
  <p class="text-warm-400 text-sm mb-8 italic">Gracias por apoyar a los artesanos de nuestra comunidad. 🕊️</p>

  <!-- Order card -->
  <div class="bg-white border border-warm-200 rounded-2xl p-6 text-left mb-8">
    <div class="grid grid-cols-2 gap-4 text-sm mb-5">
      <div>
        <p class="text-warm-500 text-xs font-medium uppercase tracking-wider mb-0.5">Comprador</p>
        <p class="font-semibold text-warm-900"><?= e($order['buyer_name']) ?></p>
      </div>
      <div>
        <p class="text-warm-500 text-xs font-medium uppercase tracking-wider mb-0.5">Teléfono</p>
        <p class="font-semibold text-warm-900"><?= e($order['contact_phone']) ?></p>
      </div>
      <div class="col-span-2">
        <p class="text-warm-500 text-xs font-medium uppercase tracking-wider mb-0.5">Dirección de entrega</p>
        <p class="font-semibold text-warm-900"><?= e($order['shipping_address']) ?></p>
      </div>
      <?php if ($order['notes']): ?>
      <div class="col-span-2">
        <p class="text-warm-500 text-xs font-medium uppercase tracking-wider mb-0.5">Notas</p>
        <p class="font-semibold text-warm-900"><?= e($order['notes']) ?></p>
      </div>
      <?php endif; ?>
    </div>

    <table class="w-full text-sm border-t border-warm-200 pt-4">
      <thead>
        <tr class="text-xs text-warm-500 uppercase tracking-wider">
          <th class="py-2 text-left">Producto</th>
          <th class="py-2 text-center">Cant.</th>
          <th class="py-2 text-right">Total</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-warm-100">
        <?php foreach ($items as $item): ?>
        <tr>
          <td class="py-2.5 text-warm-800"><?= e($item['product_name']) ?></td>
          <td class="py-2.5 text-center text-warm-600"><?= (int)$item['quantity'] ?></td>
          <td class="py-2.5 text-right font-medium"><?= formatPrice($item['unit_price'] * $item['quantity']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr class="border-t border-warm-200 font-bold text-warm-900">
          <td colspan="2" class="pt-3 text-right">Total</td>
          <td class="pt-3 text-right"><?= formatPrice($order['total']) ?></td>
        </tr>
      </tfoot>
    </table>
  </div>

  <div class="flex flex-col sm:flex-row gap-3 justify-center">
    <a href="/productos"
       class="px-6 py-3 border border-warm-300 text-warm-700 rounded-xl font-semibold text-sm hover:bg-warm-100 transition">
      Explorar más artesanías
    </a>
    <a href="/mi-cuenta"
       class="px-6 py-3 bg-brand-700 text-white rounded-xl font-semibold text-sm hover:bg-brand-800 transition">
      Ver mi cuenta →
    </a>
  </div>
</div>
