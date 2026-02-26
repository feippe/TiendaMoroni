<?php
$layout = 'layout/app';
$crumbs = [['name' => 'Inicio', 'url' => SITE_URL . '/']];
if ($parentCategory) {
    $crumbs[] = ['name' => $parentCategory['name'], 'url' => SITE_URL . '/categoria/' . $parentCategory['slug']];
}
$crumbs[] = ['name' => $category['name'], 'url' => SITE_URL . '/categoria/' . $category['slug']];
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

  <!-- Products -->
  <?php if (empty($products)): ?>
  <div class="text-center py-20 text-warm-400">
    <p class="text-lg font-medium">No hay productos en esta categoría todavía.</p>
    <a href="/productos" class="mt-2 text-sm text-brand-700 hover:underline">Ver todos →</a>
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
