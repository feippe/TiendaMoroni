<?php $layout = 'layout/app'; ?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10"
     x-data="cartPage()" x-init="init()">

  <h1 class="text-3xl font-bold text-warm-900 mb-8">Tu carrito</h1>

  <?php if (empty($items)): ?>
  <div class="text-center py-20">
    <i data-lucide="shopping-cart" class="w-16 h-16 mx-auto text-warm-300 mb-4"></i>
    <p class="text-xl font-semibold text-warm-700">Tu carrito está vacío</p>
    <p class="mt-2 text-sm text-warm-500">¡Descubrí los productos de nuestra comunidad!</p>
    <a href="/productos" class="mt-4 inline-block px-6 py-2.5 bg-brand-800 text-white rounded-xl text-sm font-semibold hover:bg-brand-700 transition">
      Ver productos
    </a>
  </div>
  <?php else: ?>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

    <!-- Items -->
    <div class="lg:col-span-2 space-y-3">
      <?php foreach ($items as $productId => $item): ?>
      <div class="bg-white border border-warm-200 rounded-2xl p-4 flex items-center gap-4"
           id="cart-item-<?= (int)$productId ?>">
        <img src="<?= e($item['image'] ?: 'https://picsum.photos/seed/default/80/80') ?>"
             alt="<?= e($item['name']) ?>" loading="lazy" width="80" height="80"
             class="w-20 h-20 rounded-xl object-cover flex-shrink-0">
        <div class="flex-1 min-w-0">
          <h3 class="font-semibold text-warm-900 text-sm truncate"><?= e($item['name']) ?></h3>
          <p class="text-warm-500 text-sm mt-0.5"><?= formatPrice($item['price']) ?> c/u</p>
        </div>
        <div class="flex items-center gap-2">
          <div class="flex items-center border border-warm-300 rounded-lg overflow-hidden text-sm">
            <button onclick="changeQty(<?= (int)$productId ?>, -1, <?= (float)$item['price'] ?>)"
                    class="px-2.5 py-1 hover:bg-warm-100 transition">−</button>
            <span class="px-3 py-1 border-x border-warm-300 font-medium" id="qty-<?= (int)$productId ?>"><?= (int)$item['qty'] ?></span>
            <button onclick="changeQty(<?= (int)$productId ?>, +1, <?= (float)$item['price'] ?>)"
                    class="px-2.5 py-1 hover:bg-warm-100 transition">+</button>
          </div>
          <span class="text-sm font-bold text-warm-900 w-20 text-right" id="item-total-<?= (int)$productId ?>">
            <?= formatPrice($item['price'] * $item['qty']) ?>
          </span>
          <button onclick="removeFromCart(<?= (int)$productId ?>)"
                  class="text-warm-300 hover:text-red-500 transition ml-1">
            <i data-lucide="x" class="w-4 h-4"></i>
          </button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Summary -->
    <div class="lg:col-span-1">
      <div class="bg-white border border-warm-200 rounded-2xl p-6 sticky top-24">
        <h2 class="font-bold text-warm-900 text-lg mb-4">Resumen</h2>
        <div class="flex justify-between text-sm text-warm-600 mb-2">
          <span>Subtotal</span>
          <span id="cart-total"><?= formatPrice($subtotal) ?></span>
        </div>
        <div class="flex justify-between text-sm text-warm-400 mb-5">
          <span>Envío</span>
          <span>A coordinar</span>
        </div>
        <div class="flex justify-between font-bold text-warm-900 text-lg border-t border-warm-200 pt-4 mb-6">
          <span>Total</span>
          <span id="cart-total-2"><?= formatPrice($subtotal) ?></span>
        </div>
        <a href="/checkout"
           class="block w-full text-center bg-brand-800 text-white py-3.5 rounded-xl font-bold text-base hover:bg-brand-700 transition">
          Finalizar compra →
        </a>
        <a href="/productos" class="block text-center text-sm text-warm-500 hover:text-brand-800 mt-3 transition">
          Seguir comprando
        </a>
      </div>
    </div>

  </div>
  <?php endif; ?>

</div>

<script>
const __fmt = new Intl.NumberFormat('es-UY');
function fmtPrice(n) { return '$ ' + __fmt.format(Math.round(n)); }

function updateTotals(data) {
  document.querySelectorAll('#cart-total, #cart-total-2').forEach(el => {
    el.textContent = fmtPrice(data.subtotal ?? 0);
  });
  if (typeof Alpine !== 'undefined' && Alpine.store('cart') && data.count !== undefined) {
    Alpine.store('cart').set(data.count);
  }
}

// delta = -1 or +1; price = unit price for per-item total recalc
function changeQty(productId, delta, price) {
  const qtyEl = document.getElementById('qty-' + productId);
  if (!qtyEl) return;
  const newQty = Math.max(0, (parseInt(qtyEl.textContent, 10) || 1) + delta);
  updateCart(productId, newQty, price);
}

function updateCart(productId, qty, price) {
  fetch('/carrito/actualizar', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ product_id: productId, qty: qty }),
  }).then(r => r.json()).then(data => {
    if (qty <= 0) {
      document.getElementById('cart-item-' + productId)?.remove();
    } else {
      const qtyEl = document.getElementById('qty-' + productId);
      if (qtyEl) qtyEl.textContent = qty;
      const totEl = document.getElementById('item-total-' + productId);
      if (totEl && price !== undefined) totEl.textContent = fmtPrice(price * qty);
    }
    updateTotals(data);
  });
}

function removeFromCart(productId) {
  fetch('/carrito/eliminar', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ product_id: productId }),
  }).then(r => r.json()).then(data => {
    document.getElementById('cart-item-' + productId)?.remove();
    updateTotals(data);
  });
}

function cartPage() { return { init() {} }; }
</script>
