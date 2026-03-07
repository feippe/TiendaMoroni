<?php
$layout = 'layout/app';
$crumbs = [['name' => 'Inicio', 'url' => SITE_URL . '/']];
if ($parentCategory) {
    $crumbs[] = ['name' => $parentCategory['name'], 'url' => SITE_URL . '/categoria/' . $parentCategory['slug']];
}
$crumbs[] = ['name' => $category['name'], 'url' => SITE_URL . '/categoria/' . $category['slug']];

/* ── JSON-LD: CollectionPage + ItemList ── */
$graphItems = [
    [
        '@type'       => 'CollectionPage',
        '@id'         => SITE_URL . '/categoria/' . $category['slug'] . '#webpage',
        'url'         => SITE_URL . '/categoria/' . $category['slug'],
        'name'        => $category['meta_title'] ?: $category['name'],
        'description' => $category['meta_description'] ?: ($category['description'] ?? ''),
        'isPartOf'    => ['@id' => SITE_URL . '/#website'],
    ],
];

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
                    '@type'         => 'Offer',
                    'price'         => number_format((float)$p['price'], 2, '.', ''),
                    'priceCurrency' => 'UYU',
                    'availability'  => $avail,
                    'url'           => SITE_URL . '/producto/' . $p['slug'],
                ],
            ],
        ];
    }
    $graphItems[] = [
        '@type'           => 'ItemList',
        'name'            => $category['name'] . ' — ' . SITE_NAME,
        'url'             => SITE_URL . '/categoria/' . $category['slug'],
        'numberOfItems'   => count($products),
        'itemListElement' => $listItems,
    ];
}

$jsonLD = json_encode([
    '@context' => 'https://schema.org',
    '@graph'   => $graphItems,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

  <?php view('partials/breadcrumbs', ['crumbs' => $crumbs]) ?>

  <!-- Header -->
  <div class="mt-6 flex items-center gap-5 mb-8">
    <?php if ($category['image_url']): ?>
    <div class="w-16 h-16 rounded-2xl overflow-hidden bg-warm-100 flex-shrink-0">
      <img src="<?= e($category['image_url']) ?>" alt="<?= e($category['name']) ?>"
           loading="eager" width="64" height="64" class="w-full h-full object-cover">
    </div>
    <?php endif; ?>
    <div>
      <h1 class="text-3xl font-bold text-warm-900"><?= e($category['name']) ?></h1>
      <?php if ($category['description']): ?>
      <p class="text-warm-500 text-sm mt-1"><?= e($category['description']) ?></p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Subcategories -->
  <?php if (!empty($subcategories)): ?>
  <div class="mb-8">
    <h2 class="text-sm font-semibold text-warm-500 uppercase tracking-wider mb-3">Subcategorías</h2>
    <div class="flex flex-wrap gap-2">
      <?php foreach ($subcategories as $sub): ?>
      <a href="/categoria/<?= e($sub['slug']) ?>"
         class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-warm-200 rounded-full text-sm text-warm-700 hover:border-brand-400 hover:text-brand-800 transition shadow-sm">
        <?php if ($sub['image_url']): ?>
        <img src="<?= e($sub['image_url']) ?>" alt="" class="w-5 h-5 rounded-full object-cover">
        <?php endif; ?>
        <?= e($sub['name']) ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Products -->
  <?php if (empty($products)): ?>
  <div class="text-center py-20 text-warm-400">
    <p class="text-lg font-medium">No hay productos en esta categoría todavía.</p>
    <a href="/productos" class="mt-2 text-sm text-brand-800 hover:underline">Ver todos →</a>
  </div>
  <?php else: ?>
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
    <?php foreach ($products as $product): ?>
    <?php include __DIR__ . '/../partials/product_card.php'; ?>
    <?php endforeach; ?>
  </div>
  <?php view('partials/pagination', ['pagination' => $pagination]) ?>
  <?php endif; ?>

</div>
