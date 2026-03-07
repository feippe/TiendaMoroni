<?php
$layout = 'layout/app';
$crumbs = [
    ['name' => 'Inicio',    'url' => SITE_URL . '/'],
    ['name' => 'Vendedores', 'url' => SITE_URL . '/productos'],
    ['name' => $vendor['business_name'], 'url' => SITE_URL . '/vendedor/' . $vendor['slug']],
];

/* ── JSON-LD: CollectionPage + ItemList ── */
$graphItems = [
    [
        '@type'       => 'CollectionPage',
        '@id'         => SITE_URL . '/vendedor/' . $vendor['slug'] . '#webpage',
        'url'         => SITE_URL . '/vendedor/' . $vendor['slug'],
        'name'        => $vendor['business_name'] . ' — ' . SITE_NAME,
        'description' => $vendor['business_description'] ?: 'Productos de ' . $vendor['business_name'],
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
        'name'            => 'Productos de ' . $vendor['business_name'],
        'url'             => SITE_URL . '/vendedor/' . $vendor['slug'],
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
    <div class="w-16 h-16 rounded-2xl bg-brand-100 flex items-center justify-center flex-shrink-0">
      <i data-lucide="store" class="w-8 h-8 text-brand-600"></i>
    </div>
    <div>
      <div class="flex items-center gap-2 flex-wrap">
        <h1 class="text-3xl font-bold text-warm-900"><?= e($vendor['business_name']) ?></h1>
        <?php if (!empty($vendor['is_verified'])): ?>
        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-green-100 text-green-700 rounded-full text-xs font-semibold">
          <i data-lucide="badge-check" class="w-3.5 h-3.5"></i> Verificado
        </span>
        <?php endif; ?>
      </div>
      <?php if (!empty($vendor['business_description'])): ?>
      <p class="text-warm-500 text-sm mt-1"><?= e($vendor['business_description']) ?></p>
      <?php endif; ?>
      <p class="text-warm-400 text-xs mt-1">
        <?= $pagination['total'] ?> producto<?= $pagination['total'] !== 1 ? 's' : '' ?> disponible<?= $pagination['total'] !== 1 ? 's' : '' ?>
      </p>
    </div>
  </div>

  <!-- Products -->
  <?php if (empty($products)): ?>
  <div class="text-center py-20 text-warm-400">
    <p class="text-lg font-medium">Este vendedor no tiene productos publicados todavía.</p>
    <a href="/productos" class="mt-2 text-sm text-brand-800 hover:underline">Ver todos los productos →</a>
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
