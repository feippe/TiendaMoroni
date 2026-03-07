<?php
$layout = 'layout/app';

/* ── JSON-LD: ItemList ── */
if (!empty($products)) {
    $listItems = [];
    foreach ($products as $i => $p) {
        $avail = ((int)($p['stock'] ?? 0)) > 0
            ? 'https://schema.org/InStock'
            : 'https://schema.org/OutOfStock';
        $listItems[] = [
            '@type'    => 'ListItem',
            'position' => $i + 1,
            'item'     => [
                '@type'  => 'Product',
                'name'   => $p['name'],
                'url'    => SITE_URL . '/producto/' . $p['slug'],
                'image'  => $p['main_image_url'] ?? '',
                'offers' => [
                    '@type'        => 'Offer',
                    'price'        => number_format((float)$p['price'], 2, '.', ''),
                    'priceCurrency'=> 'UYU',
                    'availability' => $avail,
                ],
            ],
        ];
    }
    $jsonLD = json_encode([
        '@context'        => 'https://schema.org',
        '@type'           => 'ItemList',
        'name'            => 'Productos — ' . SITE_NAME,
        'url'             => SITE_URL . '/productos',
        'numberOfItems'   => count($products),
        'itemListElement' => $listItems,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

  <!-- Breadcrumb -->
  <?php view('partials/breadcrumbs', ['crumbs' => [
    ['name' => 'Inicio',      'url' => SITE_URL . '/'],
    ['name' => 'Productos',  'url' => SITE_URL . '/productos'],
  ]]) ?>

  <h1 class="text-3xl font-bold text-warm-900 mt-4 mb-8">Productos</h1>

  <div class="flex flex-col lg:flex-row gap-8">

    <!-- Sidebar filters -->
    <aside class="lg:w-60 flex-shrink-0" x-data="{ open: false }">
      <!-- Mobile toggle -->
      <button @click="open = !open" class="lg:hidden w-full flex items-center justify-between mb-4 py-2 px-4
             bg-white border border-warm-200 rounded-xl text-sm font-medium text-warm-700">
        <span>Filtros</span>
        <svg class="w-4 h-4" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
        </svg>
      </button>

      <form method="get" action="/productos" class="bg-white border border-warm-200 rounded-2xl p-5 space-y-6"
            :class="{ 'hidden lg:block': !open, 'block': open }">

        <!-- Search -->
        <div>
          <label class="block text-xs font-semibold text-warm-600 uppercase tracking-wider mb-2">Buscar</label>
          <input type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="Buscar productos..."
                 class="w-full border border-warm-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
        </div>

        <!-- Categories -->
        <div>
          <label class="block text-xs font-semibold text-warm-600 uppercase tracking-wider mb-2">Categoría</label>
          <div class="space-y-1.5">
            <label class="flex items-center gap-2 cursor-pointer text-sm">
              <input type="radio" name="categoria" value="" <?= !$filters['category_id'] ? 'checked' : '' ?>
                     class="text-brand-600">
              <span class="text-warm-700">Todas</span>
            </label>
            <?php foreach ($categories as $cat): ?>
            <?php
              $depth  = (int)($cat['depth'] ?? 0);
              $indent = $depth * 16; // px
              $prefix = $depth > 0 ? str_repeat('&nbsp;', $depth * 2) . '&#x2514;&nbsp;' : '';
            ?>
            <label class="flex items-center gap-2 cursor-pointer text-sm" style="padding-left: <?= $indent ?>px">
              <input type="radio" name="categoria" value="<?= (int)$cat['id'] ?>"
                     <?= (int)($filters['category_id'] ?? 0) === (int)$cat['id'] ? 'checked' : '' ?>
                     class="text-brand-600 flex-shrink-0">
              <span class="text-warm-700<?= $depth > 0 ? ' text-warm-500' : '' ?>"><?= $prefix ?><?= e($cat['name']) ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Price range -->
        <div>
          <label class="block text-xs font-semibold text-warm-600 uppercase tracking-wider mb-2">Precio</label>
          <div class="flex gap-2 items-center">
            <input type="number" name="precio_min" value="<?= e($filters['min_price']) ?>"
                   placeholder="Min" min="0"
                   class="w-1/2 border border-warm-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
            <span class="text-warm-400 text-sm">–</span>
            <input type="number" name="precio_max" value="<?= e($filters['max_price']) ?>"
                   placeholder="Max" min="0"
                   class="w-1/2 border border-warm-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
          </div>
        </div>

        <!-- Sort -->
        <div>
          <label class="block text-xs font-semibold text-warm-600 uppercase tracking-wider mb-2">Ordenar por</label>
          <select name="orden"
                  class="w-full border border-warm-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
            <option value=""          <?= !$filters['sort'] ? 'selected' : '' ?>>Relevancia</option>
            <option value="price_asc" <?= $filters['sort'] === 'price_asc'  ? 'selected' : '' ?>>Precio: menor a mayor</option>
            <option value="price_desc"<?= $filters['sort'] === 'price_desc' ? 'selected' : '' ?>>Precio: mayor a menor</option>
            <option value="newest"    <?= $filters['sort'] === 'newest'     ? 'selected' : '' ?>>Más recientes</option>
          </select>
        </div>

        <button type="submit"
                class="w-full bg-brand-800 text-white py-2.5 rounded-xl text-sm font-semibold hover:bg-brand-700 transition">
          Aplicar filtros
        </button>

        <?php if (array_filter([$filters['q'], $filters['category_id'], $filters['min_price'], $filters['max_price'], $filters['sort']])): ?>
        <a href="/productos" class="block text-center text-xs text-red-500 hover:text-red-700 transition">
          Limpiar filtros
        </a>
        <?php endif; ?>
      </form>
    </aside>

    <!-- Product grid -->
    <div class="flex-1">
      <?php if (empty($products)): ?>
      <div class="text-center py-20 text-warm-400">
        <svg class="w-12 h-12 mx-auto mb-4 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9.172 16.172a4 4 0 0 1 5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
        </svg>
        <p class="text-lg font-medium">No encontramos productos con ese filtro.</p>
        <p class="mt-1 text-sm text-warm-500">Probá con otra búsqueda o explorá todas las categorías.</p>
        <a href="/productos" class="mt-3 inline-block text-sm text-brand-800 hover:underline">Ver todos los productos →</a>
      </div>
      <?php else: ?>
      <p class="text-sm text-warm-500 mb-4"><?= $pagination['total'] ?> producto<?= $pagination['total'] !== 1 ? 's' : '' ?> encontrado<?= $pagination['total'] !== 1 ? 's' : '' ?></p>
      <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-4">
        <?php foreach ($products as $product): ?>
        <?php include __DIR__ . '/../partials/product_card.php'; ?>
        <?php endforeach; ?>
      </div>
      <?php view('partials/pagination', ['pagination' => $pagination]) ?>
      <?php endif; ?>
    </div>

  </div>
</div>
