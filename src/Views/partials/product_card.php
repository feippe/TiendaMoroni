<article class="bg-white rounded-2xl overflow-hidden shadow-sm border border-warm-200
              hover:shadow-md hover:-translate-y-1 transition-all duration-200 group">
  <a href="<?= SITE_URL ?>/producto/<?= e($product['slug']) ?>" class="block">
    <div class="aspect-square overflow-hidden bg-warm-100">
      <img
        src="<?= e($product['main_image_url'] ?? 'https://picsum.photos/seed/default/400/400') ?>"
        alt="<?= e($product['name']) ?>"
        loading="lazy"
        width="400" height="400"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
    </div>
    <div class="p-4">
      <?php if (!empty($product['category_name'])): ?>
      <span class="text-xs font-medium text-navy-mid uppercase tracking-wider">
        <?= e($product['category_name']) ?>
      </span>
      <?php endif; ?>
      <h3 class="mt-1 font-semibold text-navy-deeper text-sm leading-snug line-clamp-2 group-hover:text-navy transition">
        <?= e($product['name']) ?>
      </h3>
      <?php if (!empty($product['vendor_name'])): ?>
      <p class="mt-1 text-xs text-warm-400"><?= e($product['vendor_name']) ?></p>
      <?php endif; ?>
      <?php if (!empty($product['short_description'])): ?>
      <p class="mt-1 text-xs text-warm-500 line-clamp-2"><?= e($product['short_description']) ?></p>
      <?php endif; ?>
      <div class="mt-3 flex items-center justify-between">
        <span class="text-lg font-bold text-navy-deeper"><?= formatPrice($product['price']) ?></span>
        <span class="text-xs font-semibold text-navy-deeper bg-gold-soft px-3 py-1 rounded-full">
          Ver más →
        </span>
      </div>
    </div>
  </a>
  <!-- Add to cart -->
  <div class="px-4 pb-4">
    <button
      onclick="addToCart(<?= (int) $product['id'] ?>, this)"
      class="w-full py-2 bg-navy text-white text-sm font-semibold rounded-xl
             hover:bg-navy-dark active:scale-95 transition-all duration-150">
      Agregá al carrito
    </button>
  </div>
</article>
