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
<section class="hero-bg relative overflow-hidden text-white flex items-center justify-center"
         style="min-height:85vh">

  <!-- Dark overlay for text legibility -->
  <div class="absolute inset-0 pointer-events-none" aria-hidden="true"
       style="background:linear-gradient(135deg,rgba(13,28,56,0.85) 0%,rgba(30,58,110,0.72) 100%)"></div>

  <!-- Dot texture on top of overlay -->
  <div class="hero-dots absolute inset-0 pointer-events-none" aria-hidden="true"></div>

  <div class="relative z-10 w-full max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-24 text-center">

    <!-- Headline -->
    <h1 class="hero-headline hero-item-1 font-serif text-4xl sm:text-5xl md:text-6xl font-extrabold leading-tight tracking-tight mb-6"
        style="font-family:'Playfair Display',Georgia,serif">
      Artesanías únicas<br class="hidden sm:block"> para tu fe
    </h1>

    <!-- Subheadline -->
    <p class="hero-item-2 text-base sm:text-lg md:text-xl max-w-xl mx-auto mb-10 leading-relaxed"
       style="color:rgba(255,255,255,0.78)">
      Productos creados por y para nuestra comunidad —
      tapas de libreos, llaveros, aceiteros, adornos y más.
    </p>

    <!-- CTAs -->
    <div class="hero-item-3 flex flex-col sm:flex-row gap-4 justify-center items-center">
      <a href="/productos"
         class="inline-flex items-center gap-2 px-8 py-3 bg-gold text-navy-deeper font-bold rounded-full text-sm sm:text-base transition duration-200 hover:bg-gold-dark hover:scale-105 shadow-md">
        Explorar productos
        <i data-lucide="arrow-right" class="w-4 h-4"></i>
      </a>
      <a href="/publicar-gratis"
         class="inline-flex items-center gap-2 px-8 py-3 border-2 border-white/80 text-white font-semibold rounded-full text-sm sm:text-base transition duration-200 hover:bg-white hover:text-navy-deeper">
        ¿Creás productos? Publicá gratis
      </a>
    </div>

  </div>
</section>

<!-- ── Trust bar ───────────────────────────────────────────────────────────────── -->
<div class="bg-gold text-navy-deeper">
  <div class="max-w-5xl mx-auto px-4 py-3 flex flex-wrap justify-center gap-6 md:gap-10 text-xs font-semibold tracking-wide">
    <span>✦ Artesanos verificados de nuestra comunidad</span>
    <span>✦ Productos únicos hechos a mano</span>
    <span>✦ Contacto directo con el vendedor</span>
  </div>
</div>
<!-- ── Categories ──────────────────────────────────────────────────────────── -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
  <h2 class="text-2xl font-bold text-navy-deeper mb-2 font-serif" style="font-family:'Playfair Display',Georgia,serif">Explorá por categoría</h2>
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
      <h2 class="text-2xl font-bold text-navy-deeper font-serif" style="font-family:'Playfair Display',Georgia,serif">Productos destacados</h2>
      <a href="/productos" class="text-sm font-semibold text-navy hover:text-navy-dark transition">
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
  <h2 class="text-2xl font-bold text-navy-deeper text-center mb-4 font-serif" style="font-family:'Playfair Display',Georgia,serif">¿Cómo funciona?</h2>
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
      <div class="w-16 h-16 bg-navy-light rounded-2xl flex items-center justify-center mx-auto mb-4">
        <i data-lucide="<?= $step['icon'] ?>" class="w-8 h-8 text-navy"></i>
      </div>
      <span class="text-xs font-bold text-navy-mid tracking-widest"><?= $step['num'] ?></span>
      <h3 class="text-xl font-bold text-navy-deeper mt-1 mb-2"><?= $step['title'] ?></h3>
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
      <h2 class="text-2xl font-bold text-navy-deeper font-serif" style="font-family:'Playfair Display',Georgia,serif">Recién llegados</h2>
      <a href="/productos?orden=newest" class="text-sm font-semibold text-navy hover:text-navy-dark transition">
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
<?= partial('partials/home-fe') ?>
