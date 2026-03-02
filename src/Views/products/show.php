<?php
$layout = 'layout/app';

// JSON-LD Product schema
$availability = $product['stock'] > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
$jsonLD = json_encode([
    '@context'         => 'https://schema.org',
    '@type'            => 'Product',
    'name'             => $product['name'],
    'description'      => strip_tags($product['description'] ?? $product['short_description'] ?? ''),
    'image'            => $product['main_image_url'],
    'sku'              => (string) $product['id'],
    'offers' => [
        '@type'         => 'Offer',
        'price'         => $product['price'],
        'priceCurrency' => 'UYU',
        'availability'  => $availability,
        'url'           => SITE_URL . '/producto/' . $product['slug'],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

$crumbs = [
    ['name' => 'Inicio',      'url' => SITE_URL . '/'],
    ['name' => 'Productos',  'url' => SITE_URL . '/productos'],
];
if ($category) {
    $crumbs[] = ['name' => $category['name'], 'url' => SITE_URL . '/categoria/' . $category['slug']];
}
$crumbs[] = ['name' => $product['name'], 'url' => SITE_URL . '/producto/' . $product['slug']];
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

  <!-- Breadcrumb -->
  <?php view('partials/breadcrumbs', ['crumbs' => $crumbs]) ?>

  <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-10">

    <!-- Image gallery -->
    <div x-data="{ activeImage: '<?= e($product['main_image_url']) ?>' }">
      <!-- Main image -->
      <div class="rounded-2xl overflow-hidden bg-warm-100 aspect-square">
        <img :src="activeImage" alt="<?= e($product['name']) ?>"
             loading="eager" width="600" height="600"
             class="w-full h-full object-cover transition-opacity duration-200">
      </div>

      <!-- Thumbnails -->
      <?php
      $thumbs = !empty($images)
          ? array_map(fn($i) => ['image_url' => $i['image_url']], $images)
          : ($product['main_image_url'] ? [['image_url' => $product['main_image_url']]] : []);
      ?>
      <?php if (count($thumbs) > 1): ?>
      <div class="mt-3 flex gap-2 overflow-x-auto pb-1">
        <?php foreach ($thumbs as $thumb): ?>
        <button @click="activeImage = '<?= e($thumb['image_url']) ?>'"
                :class="activeImage === '<?= e($thumb['image_url']) ?>' ? 'ring-2 ring-navy' : 'ring-1 ring-warm-200'"
                class="flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden bg-warm-100 focus:outline-none transition">
          <img src="<?= e($thumb['image_url']) ?>" alt="" loading="lazy" width="64" height="64"
               class="w-full h-full object-cover">
        </button>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Product info -->
    <div>
      <?php if (!empty($product['category_name'])): ?>
      <a href="/categoria/<?= e($product['category_slug']) ?>"
         class="text-xs font-semibold text-navy-mid uppercase tracking-wider hover:text-navy transition">
        <?= e($product['category_name']) ?>
      </a>
      <?php endif; ?>

      <h1 class="mt-2 text-3xl font-bold text-navy-deeper leading-tight"><?= e($product['name']) ?></h1>

      <?php if (!empty($product['short_description'])): ?>
      <p class="mt-3 text-warm-500 text-base leading-relaxed"><?= e($product['short_description']) ?></p>
      <?php endif; ?>

      <div class="mt-5 flex items-baseline gap-3">
        <span class="text-4xl font-extrabold text-navy-deeper"><?= formatPrice($product['price']) ?></span>
        <?php if ($product['stock'] > 0): ?>
        <span class="text-sm font-medium text-green-600 bg-green-50 px-2.5 py-1 rounded-full">En stock</span>
        <?php else: ?>
        <span class="text-sm font-medium text-red-500 bg-red-50 px-2.5 py-1 rounded-full">Sin stock</span>
        <?php endif; ?>
      </div>

      <!-- Quantity + Add to cart -->
      <?php if ($product['stock'] > 0): ?>
      <div class="mt-6" x-data="{ qty: 1 }">
        <div class="flex items-center gap-3">
          <div class="flex items-center border border-warm-300 rounded-xl overflow-hidden">
            <button @click="if(qty>1) qty--"
                    class="px-3 py-2.5 text-warm-700 hover:bg-warm-100 transition font-medium">−</button>
            <span x-text="qty" class="px-4 py-2.5 text-sm font-semibold border-x border-warm-300 min-w-[40px] text-center"></span>
            <button @click="qty++"
                    class="px-3 py-2.5 text-warm-700 hover:bg-warm-100 transition font-medium">+</button>
          </div>
          <button @click="addToCart(<?= (int)$product['id'] ?>, $el, qty)"
                  class="flex-1 bg-gold text-navy-deeper py-3 px-6 rounded-xl font-bold text-base
                         hover:bg-gold-dark active:scale-95 transition-all duration-150">
            Agregá al carrito
          </button>
        </div>
        <button @click="buyNow(<?= (int)$product['id'] ?>, $el, qty)"
                class="mt-3 w-full bg-navy text-white py-3 px-6 rounded-xl font-bold text-base
                       hover:bg-navy-dark active:scale-95 transition-all duration-150">
          Comprar ahora →
        </button>
      </div>
      <?php else: ?>
      <div class="mt-6">
        <button disabled class="w-full bg-warm-200 text-warm-500 py-3 px-6 rounded-xl font-bold text-base cursor-not-allowed">
          Sin stock
        </button>
      </div>
      <?php endif; ?>

      <!-- Vendor -->
      <?php if (!empty($product['vendor_name'])): ?>
      <div class="mt-6 pt-5 border-t border-warm-100 flex items-center gap-2 text-sm text-warm-500">
        <i data-lucide="store" class="w-4 h-4 flex-shrink-0"></i>
        Vendido por
        <a href="/vendedor/<?= e($product['vendor_slug'] ?? '') ?>"
           class="font-medium text-warm-800 hover:text-navy transition">
          <?= e($product['vendor_name']) ?>
        </a>
      </div>
      <?php endif; ?>

      <!-- Description -->
      <?php if (!empty($product['description'])): ?>
      <div class="mt-8 pt-6 border-t border-warm-200">
        <h2 class="font-semibold text-navy-deeper mb-3">Sobre este producto</h2>
        <div class="prose prose-sm max-w-none text-warm-600">
          <?= $product['description'] ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

  </div>

  <!-- Vendor products slider -->
  <?php if (!empty($vendorProducts)): ?>
  <section class="mt-16" x-data="{
    el: null,
    canPrev: false,
    canNext: true,
    init() {
      this.el = this.$refs.slider;
      this.el.addEventListener('scroll', () => this.updateNav(), { passive: true });
      this.$nextTick(() => this.updateNav());
    },
    updateNav() {
      this.canPrev = this.el.scrollLeft > 4;
      this.canNext = this.el.scrollLeft + this.el.offsetWidth < this.el.scrollWidth - 4;
    },
    scroll(dir) {
      this.el.scrollBy({ left: dir * (this.el.offsetWidth * 0.75), behavior: 'smooth' });
    }
  }">
    <div class="flex items-center justify-between mb-5">
      <h2 class="text-xl font-bold text-navy-deeper">
        Más de <span class="text-navy"><?= e($product['vendor_name']) ?></span>
      </h2>
      <div class="flex items-center gap-2">
        <button @click="scroll(-1)" :disabled="!canPrev"
                class="w-9 h-9 rounded-full border border-warm-200 flex items-center justify-center text-warm-600
                       hover:border-gold hover:text-navy transition disabled:opacity-30 disabled:cursor-not-allowed">
          <i data-lucide="chevron-left" class="w-4 h-4"></i>
        </button>
        <button @click="scroll(1)" :disabled="!canNext"
                class="w-9 h-9 rounded-full border border-warm-200 flex items-center justify-center text-warm-600
                       hover:border-gold hover:text-navy transition disabled:opacity-30 disabled:cursor-not-allowed">
          <i data-lucide="chevron-right" class="w-4 h-4"></i>
        </button>
      </div>
    </div>

    <div x-ref="slider"
         class="flex gap-4 overflow-x-auto pb-3 scroll-smooth snap-x snap-mandatory"
         style="scrollbar-width: none; -ms-overflow-style: none;">

      <?php $mainProduct = $product; ?>
      <?php foreach ($vendorProducts as $product): ?>
      <div class="flex-shrink-0 snap-start" style="width: clamp(180px, 22%, 220px)">
        <?php include __DIR__ . '/../partials/product_card.php'; ?>
      </div>
      <?php endforeach; ?>
      <?php $product = $mainProduct; unset($mainProduct); ?>

      <!-- "Ver más" card -->
      <div class="flex-shrink-0 snap-start" style="width: clamp(180px, 22%, 220px)">
        <a href="/vendedor/<?= e($product['vendor_slug'] ?? '') ?>"
           class="flex flex-col items-center justify-center h-full min-h-[240px] rounded-2xl border-2 border-dashed
                  border-warm-300 text-warm-500 hover:border-gold hover:text-navy transition group p-6 text-center">
          <i data-lucide="layout-grid" class="w-8 h-8 mb-3 opacity-60 group-hover:opacity-100 transition"></i>
          <span class="text-sm font-semibold">Ver todos los productos</span>
          <span class="text-xs mt-1 opacity-70">de <?= e($product['vendor_name']) ?></span>
        </a>
      </div>

    </div>
  </section>
  <?php endif; ?>

  <!-- Q&A Section -->
  <section class="mt-16 max-w-3xl">
    <h2 class="text-2xl font-bold text-navy-deeper mb-6 font-serif" style="font-family:'Playfair Display',Georgia,serif">Preguntas al vendedor</h2>

    <?php if ($questions): ?>
    <div class="space-y-4 mb-8">
      <?php foreach ($questions as $q): ?>
      <div class="bg-white border border-warm-200 rounded-2xl p-5">
        <p class="font-medium text-navy-deeper">
          <span class="text-navy font-bold">P:</span> <?= e($q['question']) ?>
          <span class="text-xs text-warm-400 ml-2"><?= e($q['user_name']) ?> · <?= date('d/m/Y', strtotime($q['created_at'])) ?></span>
        </p>
        <?php if ($q['answer']): ?>
        <p class="mt-2 text-sm text-warm-600 pl-4 border-l-2 border-navy-light">
          <span class="font-semibold text-navy">R:</span> <?= e($q['answer']) ?>
          <span class="text-xs text-warm-400 ml-2"><?= date('d/m/Y', strtotime($q['answered_at'])) ?></span>
        </p>
        <?php else: ?>
        <p class="mt-2 text-xs text-warm-400 italic pl-4">Aún sin respuesta…</p>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="text-warm-400 text-sm mb-6">Todavía no hay preguntas para este producto. ¡Sé el primero en preguntar!</p>
    <?php endif; ?>

    <!-- Ask question -->
    <?php if (\TiendaMoroni\Core\Session::isLoggedIn()): ?>
    <div class="bg-navy-light/30 border border-navy-light rounded-2xl p-5">
      <h3 class="font-semibold text-navy-deeper mb-3">Hacé tu pregunta al vendedor</h3>
      <?php if ($questionSaved): ?>
      <div class="text-sm text-green-700 bg-green-50 rounded-lg px-4 py-2 mb-3">
        ¡Pregunta enviada! El vendedor responderá pronto.
      </div>
      <?php endif; ?>
      <?php if ($questionError): ?>
      <div class="text-sm text-red-600 bg-red-50 rounded-lg px-4 py-2 mb-3"><?= e($questionError) ?></div>
      <?php endif; ?>
      <form method="post">
        <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
        <input type="hidden" name="ask_question" value="1">
        <textarea name="question" rows="3" required
                  placeholder="Escribí tu pregunta al vendedor sobre este producto..."
                  class="w-full border border-warm-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-gold transition resize-none"></textarea>
        <button type="submit"
                class="mt-3 px-5 py-2.5 bg-navy text-white text-sm font-semibold rounded-xl hover:bg-navy-dark transition">
          Enviar pregunta
        </button>
      </form>
    </div>
    <?php else: ?>
    <div class="bg-warm-100 border border-warm-200 rounded-2xl p-5 text-center">
      <p class="text-warm-600 text-sm">
        <a href="/auth/login?redirect=<?= urlencode('/producto/' . $product['slug']) ?>"
           class="font-semibold text-navy hover:text-navy-dark transition">
          Iniciá sesión
        </a>
        para hacer una pregunta.
      </p>
    </div>
    <?php endif; ?>
  </section>

</div>
