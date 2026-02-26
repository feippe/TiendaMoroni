<?php $layout = 'layout/app'; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
  <h1 class="text-3xl font-bold text-warm-900 mb-2">
    Resultados para "<?= e($q) ?>"
  </h1>
  <p class="text-warm-500 text-sm mb-8"><?= $pagination['total'] ?> resultado<?= $pagination['total'] !== 1 ? 's' : '' ?></p>

  <?php if (empty($products)): ?>
  <div class="text-center py-20 text-warm-400">
    <p class="text-lg font-medium">No encontramos productos para "<?= e($q) ?>".</p>
    <a href="/productos" class="mt-2 text-sm text-brand-700 hover:underline">Ver todos los productos →</a>
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
