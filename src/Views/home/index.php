<?php
$layout  = 'layout/app';
$jsonLD  = json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type'  => 'WebSite',
            '@id'    => SITE_URL . '/#website',
            'url'    => SITE_URL,
            'name'   => SITE_NAME,
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => ['@type' => 'EntryPoint', 'urlTemplate' => SITE_URL . '/buscar?q={search_term_string}'],
                'query-input' => 'required name=search_term_string',
            ],
        ],
        [
            '@type' => 'Organization',
            '@id'   => SITE_URL . '/#organization',
            'name'  => SITE_NAME,
            'url'   => SITE_URL,
            'email' => SITE_EMAIL,
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>

<!-- ── Hero ────────────────────────────────────────────────────────────────── -->
<section class="relative overflow-hidden bg-gradient-to-br from-brand-900 via-brand-700 to-brand-500 text-white"
         style="background:#4c1d95">
  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 md:py-32 text-center">
    <h1 class="text-4xl md:text-6xl font-extrabold leading-tight tracking-tight mb-6"
        x-data x-intersect.once="$el.style.opacity='1';$el.style.transform='none'"
        style="opacity:0;transform:translateY(24px);transition:opacity .5s ease,transform .5s ease;">
      Todo lo que necesitás,<br class="hidden md:block"> en un solo lugar.
    </h1>
    <p class="text-lg md:text-xl text-brand-100 max-w-2xl mx-auto mb-8"
       x-data x-intersect.once="$el.style.opacity='1';$el.style.transform='none';$el.style.color='#ede9fe'"
       style="opacity:0;transform:translateY(24px);transition:opacity .5s .1s ease,transform .5s .1s ease;color:#ede9fe">
      Electrónica, moda, hogar y mucho más — al mejor precio, con entrega rápida.
    </p>
    <div class="flex flex-col sm:flex-row gap-3 justify-center"
         x-data x-intersect.once="$el.style.opacity='1';$el.style.transform='none'"
         style="opacity:0;transform:translateY(24px);transition:opacity .5s .2s ease,transform .5s .2s ease;">
      <a href="/productos"
         class="px-8 py-3.5 bg-white text-brand-700 font-bold rounded-xl text-base hover:bg-brand-50 transition shadow-lg"
         style="color:#6d28d9">
        Ver productos
      </a>
      <a href="/auth/register"
         class="px-8 py-3.5 border-2 border-white text-white font-bold rounded-xl text-base hover:bg-white/10 transition">
        Crear cuenta gratis
      </a>
    </div>
  </div>
</section>
<!-- ── Trust bar ───────────────────────────────────────────────────────────────── -->
<div style="background:#C9A84C" class="text-brand-900">
  <div class="max-w-7xl mx-auto px-4 py-3 flex flex-wrap justify-center gap-6 md:gap-12 text-xs font-semibold">
    <span>✓ Artesanos verificados de nuestra comunidad</span>
    <span>✓ Productos únicos hechos a mano</span>
    <span>✓ Contacto directo con el vendedor</span>
  </div>
</div>
<!-- ── Categories ──────────────────────────────────────────────────────────── -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
  <h2 class="text-2xl font-bold text-warm-800 mb-2 font-serif" style="font-family:'Playfair Display',Georgia,serif">Explorá por categoría</h2>
  <p class="text-warm-500 text-sm mb-8">Productos organizados con cariño para vos</p>
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-<?= min(count($categories), 4) ?> gap-4">
    <?php foreach ($categories as $cat): ?>
    <a href="/categoria/<?= e($cat['slug']) ?>"
       class="group relative overflow-hidden rounded-2xl aspect-[4/3] bg-warm-200
              hover:shadow-lg hover:-translate-y-1 transition-all duration-200">
      <img src="<?= e($cat['image_url'] ?: 'https://picsum.photos/seed/' . $cat['slug'] . '/600/400') ?>"
           alt="<?= e($cat['name']) ?>"
           loading="lazy" width="600" height="400"
           class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
      <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
      <div class="absolute bottom-0 left-0 right-0 p-4">
        <p class="text-white font-bold text-lg leading-tight"><?= e($cat['name']) ?></p>
        <?php if (!empty($cat['product_count'])): ?>
        <p class="text-white/70 text-xs"><?= (int)$cat['product_count'] ?> productos</p>
        <?php endif; ?>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- ── Featured products ───────────────────────────────────────────────────── -->
<?php if ($featured): ?>
<section class="bg-white py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-8">
      <h2 class="text-2xl font-bold text-warm-800 font-serif" style="font-family:'Playfair Display',Georgia,serif">Productos destacados</h2>
      <a href="/productos" class="text-sm font-semibold text-brand-700 hover:text-brand-900 transition">
        Ver todos →
      </a>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
      <?php foreach ($featured as $product): ?>
      <?php include __DIR__ . '/../partials/product_card.php'; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ── How it works ────────────────────────────────────────────────────────── -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
  <h2 class="text-2xl font-bold text-warm-800 text-center mb-4 font-serif" style="font-family:'Playfair Display',Georgia,serif">¿Cómo funciona?</h2>
  <p class="text-center text-warm-500 text-sm mb-12">Simple, cercano y sin complicaciones</p>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
    <?php
    $steps = [
      ['num'=>'01','title'=>'Descubrí','desc'=>'Navegá entre los productos de los artesanos y vendedores de tu comunidad, filtrá por categoría y encontrá ese regalo especial.','icon'=>'search'],
      ['num'=>'02','title'=>'Contactá','desc'=>'¿Tenés dudas sobre un producto? Comunicate directo con el vendedor usando la sección de preguntas de cada producto.','icon'=>'message-circle'],
      ['num'=>'03','title'=>'Comprá','desc'=>'Complétá tu pedido en segundos y apoyá directamente a los artesanos y vendedores de nuestra comunidad.','icon'=>'shopping-cart'],
    ];
    ?>
    <?php foreach ($steps as $step): ?>
    <div class="bg-white rounded-2xl shadow-sm border border-warm-100 text-center p-8"
         x-data x-intersect.once="$el.style.opacity='1';$el.style.transform='none'"
         style="opacity:0;transform:translateY(16px);transition:opacity .4s ease,transform .4s ease;">
      <div class="w-16 h-16 bg-brand-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <i data-lucide="<?= $step['icon'] ?>" class="w-8 h-8 text-brand-700"></i>
      </div>
      <span class="text-xs font-bold text-brand-400 tracking-widest"><?= $step['num'] ?></span>
      <h3 class="text-xl font-bold text-warm-900 mt-1 mb-2"><?= $step['title'] ?></h3>
      <p class="text-warm-500 text-sm leading-relaxed"><?= $step['desc'] ?></p>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ── Recent products ─────────────────────────────────────────────────────── -->
<?php if ($recent): ?>
<section class="bg-warm-100 py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-8">
      <h2 class="text-2xl font-bold text-warm-800 font-serif" style="font-family:'Playfair Display',Georgia,serif">Recién llegados</h2>
      <a href="/productos?orden=newest" class="text-sm font-semibold text-brand-700 hover:text-brand-900 transition">
        Ver todos →
      </a>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <?php foreach ($recent as $product): ?>
      <?php include __DIR__ . '/../partials/product_card.php'; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
