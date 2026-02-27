<?php $layout = 'layout/admin'; ?>

<div class="flex items-center justify-between mb-6">
  <h2 class="text-xl font-bold text-warm-900">Productos (<?= count($products) ?>)</h2>
  <a href="/admin/productos/nuevo"
     class="px-4 py-2 bg-brand-800 text-white text-sm font-semibold rounded-xl hover:bg-brand-700 transition">
    + Nuevo producto
  </a>
</div>

<!-- Filters -->
<form method="get" class="mb-4 flex flex-wrap gap-2">
  <input type="text" name="q" value="<?= e($q) ?>" placeholder="Buscar por nombre..."
         class="flex-1 min-w-[180px] border border-warm-300 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">

  <select name="vendor_id"
          class="border border-warm-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition bg-white">
    <option value="">Todos los vendedores</option>
    <?php foreach ($vendors as $v): ?>
    <option value="<?= (int)$v['id'] ?>" <?= (int)$vendorId === (int)$v['id'] ? 'selected' : '' ?>>
      <?= e($v['business_name']) ?>
    </option>
    <?php endforeach; ?>
  </select>

  <select name="category_id"
          class="border border-warm-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition bg-white">
    <option value="">Todas las categorías</option>
    <?php foreach ($categories as $cat): ?>
    <option value="<?= (int)$cat['id'] ?>" <?= (int)$categoryId === (int)$cat['id'] ? 'selected' : '' ?>>
      <?= e($cat['name']) ?>
    </option>
    <?php endforeach; ?>
  </select>

  <button type="submit"
          class="px-4 py-2 bg-warm-700 text-white text-sm font-semibold rounded-xl hover:bg-warm-900 transition">
    Filtrar
  </button>
  <?php if ($q || $vendorId || $categoryId): ?>
  <a href="/admin/productos"
     class="px-4 py-2 border border-warm-300 rounded-xl text-sm text-warm-600 hover:border-warm-500 transition">
    Limpiar
  </a>
  <?php endif; ?>
</form>

<div class="bg-white rounded-2xl border border-warm-200 overflow-hidden">
  <?php if (empty($products)): ?>
  <div class="text-center py-12 text-warm-400">No hay productos.</div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-warm-50 text-xs text-warm-500 uppercase tracking-wider">
        <tr>
          <th class="px-4 py-3 text-left">Producto</th>
          <th class="px-4 py-3 text-left">Vendedor</th>
          <th class="px-4 py-3 text-left">Categoría</th>
          <th class="px-4 py-3 text-left">Precio</th>
          <th class="px-4 py-3 text-left">Stock</th>
          <th class="px-4 py-3 text-left">Estado</th>
          <th class="px-4 py-3 text-left">Destacado</th>
          <th class="px-4 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-warm-100">
        <?php foreach ($products as $p): ?>
        <tr class="hover:bg-warm-50 transition">
          <td class="px-4 py-3">
            <div class="flex items-center gap-3">
              <img src="<?= e($p['main_image_url'] ?: 'https://picsum.photos/seed/default/40/40') ?>"
                   alt="<?= e($p['name']) ?>" loading="lazy" width="40" height="40"
                   class="w-10 h-10 rounded-lg object-cover flex-shrink-0">
              <span class="font-medium text-warm-900 line-clamp-2 max-w-[200px]"><?= e($p['name']) ?></span>
            </div>
          </td>
          <td class="px-4 py-3 text-warm-500"><?= e($p['vendor_name'] ?? '–') ?></td>
          <td class="px-4 py-3 text-warm-500"><?= e($p['category_name'] ?? '–') ?></td>
          <td class="px-4 py-3 font-semibold"><?= formatPrice($p['price']) ?></td>
          <td class="px-4 py-3 text-warm-600"><?= (int)$p['stock'] ?></td>
          <td class="px-4 py-3">
            <?php $statusMap = ['active'=>'bg-green-100 text-green-700','inactive'=>'bg-red-100 text-red-700','draft'=>'bg-yellow-100 text-yellow-700']; ?>
            <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= $statusMap[$p['status']] ?? 'bg-warm-100 text-warm-600' ?>">
              <?= $p['status'] ?>
            </span>
          </td>
          <td class="px-4 py-3 text-center"><?= $p['featured'] ? '⭐' : '–' ?></td>
          <td class="px-4 py-3">
            <div class="flex items-center gap-2">
              <a href="/admin/productos/<?= (int)$p['id'] ?>/editar"
                 class="text-brand-800 hover:text-brand-900 transition text-xs font-medium">Editar</a>
              <form method="post" action="/admin/productos/<?= (int)$p['id'] ?>/eliminar"
                    onsubmit="return confirm('¿Eliminar este producto?')">
                <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
                <button type="submit" class="text-red-500 hover:text-red-700 transition text-xs font-medium">
                  Eliminar
                </button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
