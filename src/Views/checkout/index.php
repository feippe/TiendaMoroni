<?php $layout = 'layout/app'; ?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
  <h1 class="text-3xl font-bold text-warm-900 mb-2 font-serif" style="font-family:'Playfair Display',Georgia,serif">Finalizar pedido</h1>
  <p class="text-warm-400 text-sm mb-8">Completá tus datos y el vendedor se pondrá en contacto para coordinar la entrega.</p>

  <?php if (!empty($error)): ?>
  <div class="mb-6 bg-red-50 border border-red-200 text-red-700 rounded-xl px-5 py-3 text-sm">
    <?= e($error) ?>
  </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

    <!-- Checkout form -->
    <div class="lg:col-span-2">
      <form method="post" action="/checkout/procesar"
            class="bg-white border border-warm-200 rounded-2xl p-6 space-y-5">
        <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">

        <h2 class="text-lg font-bold text-warm-900">Datos de contacto</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-warm-700 mb-1">Nombre completo *</label>
            <input type="text" name="full_name" required
                   value="<?= e($user['name'] ?? '') ?>"
                   class="w-full border border-warm-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
          </div>
          <div>
            <label class="block text-sm font-medium text-warm-700 mb-1">Teléfono *</label>
            <input type="tel" name="phone" required placeholder="+598 99 000 000"
                   value="<?= e($lastOrder['contact_phone'] ?? '') ?>"
                   class="w-full border border-warm-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
          </div>
        </div>

        <h2 class="text-lg font-bold text-warm-900 pt-2">Dirección de envío</h2>

        <div>
          <label class="block text-sm font-medium text-warm-700 mb-1">Calle y número *</label>
          <input type="text" name="street" required placeholder="Av. 18 de Julio 1234"
                 class="w-full border border-warm-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-warm-700 mb-1">Ciudad *</label>
            <input type="text" name="city" required placeholder="Montevideo"
                   class="w-full border border-warm-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
          </div>
          <div>
            <label class="block text-sm font-medium text-warm-700 mb-1">Departamento *</label>
            <select name="department" required
                    class="w-full border border-warm-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
              <option value="">Seleccioná...</option>
              <?php foreach (['Artigas','Canelones','Cerro Largo','Colonia','Durazno','Flores','Florida','Lavalleja','Maldonado','Montevideo','Paysandú','Río Negro','Rivera','Rocha','Salto','San José','Soriano','Tacuarembó','Treinta y Tres'] as $dept): ?>
              <option value="<?= $dept ?>"><?= $dept ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-warm-700 mb-1">Notas del pedido (opcional)</label>
          <textarea name="notes" rows="3" placeholder="Instrucciones adicionales, personalización, horario preferido para la entrega..."
                    class="w-full border border-warm-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition resize-none"></textarea>
        </div>

        <button type="submit"
                class="w-full bg-brand-700 text-white py-4 rounded-xl font-bold text-base
                       hover:bg-brand-800 active:scale-95 transition-all duration-150">
          Confirmar pedido →
        </button>
      </form>
    </div>

    <!-- Order summary -->
    <div>
      <div class="bg-white border border-warm-200 rounded-2xl p-6 sticky top-24">
        <h2 class="font-bold text-warm-900 text-lg mb-4">Tu pedido</h2>

        <div class="space-y-3 mb-5">
          <?php foreach ($items as $productId => $item): ?>
          <div class="flex items-center gap-3">
            <img src="<?= e($item['image'] ?: 'https://picsum.photos/seed/default/48/48') ?>"
                 alt="<?= e($item['name']) ?>" loading="lazy" width="48" height="48"
                 class="w-12 h-12 rounded-lg object-cover flex-shrink-0">
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-warm-900 truncate"><?= e($item['name']) ?></p>
              <p class="text-xs text-warm-500">x<?= (int)$item['qty'] ?></p>
            </div>
            <span class="text-sm font-bold text-warm-900 flex-shrink-0">
              <?= formatPrice($item['price'] * $item['qty']) ?>
            </span>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="border-t border-warm-200 pt-4 space-y-2">
          <div class="flex justify-between text-sm text-warm-600">
            <span>Subtotal</span>
            <span><?= formatPrice($subtotal) ?></span>
          </div>
          <div class="flex justify-between text-sm text-warm-400">
            <span>Envío</span>
            <span>A coordinar</span>
          </div>
          <div class="flex justify-between font-bold text-warm-900 text-lg pt-2 border-t border-warm-200 mt-2">
            <span>Total</span>
            <span><?= formatPrice($subtotal) ?></span>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
