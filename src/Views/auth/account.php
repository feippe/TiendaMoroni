<?php $layout = 'layout/app'; ?>

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

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
  <h1 class="text-3xl font-bold text-warm-900 mb-8 font-serif" style="font-family:'Playfair Display',Georgia,serif">Mi cuenta</h1>

  <!-- Profile card -->
  <div class="bg-white border border-warm-200 rounded-2xl p-6 flex items-center gap-5 mb-6">
    <?php if (!empty($user['avatar_url'])): ?>
    <img src="<?= e($user['avatar_url']) ?>" alt="<?= e($user['name']) ?>"
         class="w-16 h-16 rounded-full object-cover border-2 border-brand-200">
    <?php else: ?>
    <div class="w-16 h-16 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 font-bold text-2xl">
      <?= strtoupper(mb_substr($user['name'], 0, 1)) ?>
    </div>
    <?php endif; ?>
    <div class="flex-1">
      <p class="text-xl font-bold text-warm-900"><?= e($user['name']) ?></p>
      <p class="text-warm-500 text-sm"><?= e($user['email']) ?></p>
      <span class="inline-block mt-1 text-xs font-medium px-2.5 py-0.5 rounded-full
                   <?= $user['role'] === 'admin' ? 'bg-brand-100 text-brand-700' : 'bg-warm-100 text-warm-600' ?>">
        <?= $user['role'] === 'admin' ? 'Administrador' : 'Comprador' ?>
      </span>
    </div>
  </div>

  <!-- Quick links -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-8">
    <a href="/productos"
       class="flex items-center gap-3 bg-white border border-warm-200 rounded-2xl p-4 hover:border-brand-300 hover:shadow-sm transition">
      <div class="w-9 h-9 bg-brand-100 rounded-xl flex items-center justify-center flex-shrink-0">
        <i data-lucide="store" class="w-5 h-5 text-brand-700"></i>
      </div>
      <div>
        <p class="font-semibold text-warm-900 text-sm">Explorar</p>
        <p class="text-xs text-warm-500">Ver productos</p>
      </div>
    </a>

    <a href="/carrito"
       class="flex items-center gap-3 bg-white border border-warm-200 rounded-2xl p-4 hover:border-brand-300 hover:shadow-sm transition">
      <div class="w-9 h-9 bg-brand-100 rounded-xl flex items-center justify-center flex-shrink-0">
        <i data-lucide="shopping-cart" class="w-5 h-5 text-brand-700"></i>
      </div>
      <div>
        <p class="font-semibold text-warm-900 text-sm">Carrito</p>
        <p class="text-xs text-warm-500">Ver artículos</p>
      </div>
    </a>

    <?php if ($user['role'] === 'admin'): ?>
    <a href="/admin"
       class="flex items-center gap-3 bg-brand-700 text-white rounded-2xl p-4 hover:bg-brand-800 transition">
      <div class="w-9 h-9 bg-brand-600 rounded-xl flex items-center justify-center flex-shrink-0">
        <i data-lucide="layout-dashboard" class="w-5 h-5 text-white"></i>
      </div>
      <div>
        <p class="font-semibold text-sm">Administración</p>
        <p class="text-xs opacity-75">Panel de control</p>
      </div>
    </a>
    <?php else: ?>
    <a href="/auth/logout"
       class="flex items-center gap-3 bg-white border border-warm-200 rounded-2xl p-4 hover:border-red-200 hover:bg-red-50 transition">
      <div class="w-9 h-9 bg-red-50 rounded-xl flex items-center justify-center flex-shrink-0">
        <i data-lucide="log-out" class="w-5 h-5 text-red-500"></i>
      </div>
      <div>
        <p class="font-semibold text-warm-900 text-sm">Cerrar sesión</p>
        <p class="text-xs text-warm-500">Salir</p>
      </div>
    </a>
    <?php endif; ?>
  </div>

  <!-- Order history -->
  <div>
    <h2 class="text-xl font-bold text-warm-900 mb-4">Mis pedidos</h2>

    <?php if (empty($orders)): ?>
    <div class="bg-white border border-warm-200 rounded-2xl p-10 text-center text-warm-400">
      <i data-lucide="file-text" class="w-12 h-12 mx-auto mb-3 opacity-40"></i>
      <p class="font-medium text-sm">Aún no realizaste ningún pedido.</p>
      <a href="/productos" class="inline-block mt-4 px-5 py-2 bg-brand-700 text-white rounded-xl text-sm font-semibold hover:bg-brand-800 transition">
        Explorar productos
      </a>
    </div>
    <?php else: ?>
    <div class="space-y-4">
      <?php foreach ($orders as $order): ?>
      <div class="bg-white border border-warm-200 rounded-2xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-warm-100">
          <div class="flex items-center gap-3">
            <span class="font-mono text-sm font-bold text-warm-700">#<?= (int)$order['id'] ?></span>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $statusColors[$order['status']] ?? 'bg-warm-100 text-warm-700' ?>">
              <?= $statusLabels[$order['status']] ?? ucfirst($order['status']) ?>
            </span>
          </div>
          <div class="text-right">
            <p class="font-bold text-warm-900"><?= formatPrice($order['total']) ?></p>
            <p class="text-xs text-warm-400"><?= date('d/m/Y', strtotime($order['created_at'])) ?></p>
          </div>
        </div>
        <div class="px-5 py-3 flex items-center justify-between gap-3">
          <div class="text-sm text-warm-600 truncate flex-1">
            <i data-lucide="map-pin" class="w-4 h-4 inline-block mr-1 text-warm-400"></i>
            <?= e($order['shipping_address']) ?>
          </div>
          <a href="/checkout/confirmacion?orden=<?= (int)$order['id'] ?>"
             class="flex-shrink-0 text-sm font-semibold text-brand-700 hover:text-brand-900 transition whitespace-nowrap">
            Ver detalle →
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if ($user['role'] === 'admin'): ?>
    <div class="mt-4 text-center">
      <a href="/auth/logout"
         class="inline-flex items-center gap-2 text-sm text-warm-500 hover:text-red-500 transition">
        <i data-lucide="log-out" class="w-4 h-4"></i>
        Cerrar sesión
      </a>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
