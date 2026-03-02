<?php
$layout = 'layout/app';
$jsonLD = json_encode([
    '@context'    => 'https://schema.org',
    '@type'       => 'WebPage',
    'name'        => 'Publicá tus artesanías gratis | Tienda Moroni',
    'description' => '¿Creás productos artesanales inspirados en la fe? Publicá gratis en Tienda Moroni y llegá a toda la comunidad en Uruguay.',
    'url'         => 'https://tiendamoroni.com/publicar-gratis',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>

<!-- ── Hero ──────────────────────────────────────────────────────────────────── -->
<section class="relative overflow-hidden flex items-center justify-center text-white"
         style="min-height:72vh;background:linear-gradient(135deg,var(--color-navy-deeper) 0%,var(--color-navy-dark) 100%)">
  <div class="hero-dots absolute inset-0 pointer-events-none" aria-hidden="true"></div>
  <div class="absolute inset-0 pointer-events-none" aria-hidden="true"
       style="background:radial-gradient(ellipse 65% 55% at 50% 45%,rgba(232,176,32,0.07) 0%,transparent 70%)"></div>

  <div class="relative w-full max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-24 text-center">
    <div class="hero-item-1 inline-flex items-center gap-2 mb-6 text-xs font-semibold tracking-widest uppercase"
         style="color:var(--color-gold)">
      <i data-lucide="store" class="w-4 h-4"></i>
      Para creadores y vendedores
    </div>

    <h1 class="hero-headline hero-item-2 font-serif text-4xl sm:text-5xl md:text-6xl font-extrabold leading-tight tracking-tight mb-6"
        style="font-family:'Playfair Display',Georgia,serif">
      Compartí tu arte con<br class="hidden sm:block"> nuestra comunidad
    </h1>

    <p class="hero-item-3 text-base sm:text-lg md:text-xl max-w-xl mx-auto leading-relaxed"
       style="color:rgba(255,255,255,0.75)">
      Tienda Moroni es el lugar donde los creadores y vendedores de la Iglesia encuentran
      a quienes valoran lo que crean.
    </p>

    <div class="hero-item-3 mt-10">
      <a href="#formulario"
         class="inline-flex items-center gap-2 px-8 py-3 font-bold rounded-full text-sm sm:text-base transition duration-200 hover:scale-105 shadow-md"
         style="background:var(--color-gold);color:var(--color-navy-deeper)">
        Quiero publicar gratis
        <i data-lucide="arrow-down" class="w-4 h-4"></i>
      </a>
    </div>
  </div>
</section>

<!-- ── What can you sell ─────────────────────────────────────────────────────── -->
<section class="bg-warm-50 py-20">
  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-12">
      <h2 class="font-serif text-3xl sm:text-4xl font-bold text-warm-900 mb-3"
          style="font-family:'Playfair Display',Georgia,serif">
        ¿Qué podés vender en Tienda Moroni?
      </h2>
      <p class="text-warm-500 text-sm">Todo tipo de producto hecho con fe y dedicación.</p>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 gap-5" id="pub-cards">
      <?php
      $categories = [
        ['icon' => 'book-open',  'name' => 'Portadas para escrituras',
         'desc' => 'Diseños únicos para el Libro de Mormón, la Biblia y más.'],
        ['icon' => 'key-round',  'name' => 'Llaveros y accesorios',
         'desc' => 'Recordatorios de fe para llevar siempre con vos.'],
        ['icon' => 'star',       'name' => 'Ornamentos y decoración',
         'desc' => 'Piezas para el hogar con simbolismo especial.'],
        ['icon' => 'gift',       'name' => 'Regalos y recordatorios',
         'desc' => 'Detalles perfectos para bautismos, misiones y bodas.'],
        ['icon' => 'pencil',     'name' => 'Arte y manualidades',
         'desc' => 'Cuadros, bordados y creaciones artísticas.'],
        ['icon' => 'heart',      'name' => 'Otros artesanales',
         'desc' => 'Si lo hacés con amor y tiene significado, tiene lugar acá.'],
      ];
      foreach ($categories as $i => $cat):
      ?>
      <div class="pub-card bg-white rounded-2xl shadow-sm p-6 flex flex-col items-center text-center
                  hover:shadow-md hover:-translate-y-0.5 transition-all duration-200"
           style="transition-delay:<?= $i * 80 ?>ms">
        <div class="w-12 h-12 rounded-full flex items-center justify-center mb-4"
             style="background:rgba(232,176,32,0.12)">
          <i data-lucide="<?= $cat['icon'] ?>" class="w-6 h-6" style="color:var(--color-gold)"></i>
        </div>
        <h3 class="font-serif font-bold text-warm-800 mb-1 text-sm sm:text-base leading-tight"
            style="font-family:'Playfair Display',Georgia,serif">
          <?= $cat['name'] ?>
        </h3>
        <p class="text-warm-500 text-xs leading-relaxed"><?= $cat['desc'] ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── Why TiendaMoroni ──────────────────────────────────────────────────────── -->
<section class="py-20" style="background:var(--color-navy-deeper)">
  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
    <h2 class="font-serif text-3xl sm:text-4xl font-bold text-white text-center mb-14"
        style="font-family:'Playfair Display',Georgia,serif">
      ¿Por qué publicar con nosotros?
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
      <?php
      $benefits = [
        ['icon' => 'users',        'title' => 'Una comunidad que te conoce',
         'desc' => 'Llegás directamente a miembros de la Iglesia en Uruguay que buscan exactamente lo que vos creás.'],
        ['icon' => 'badge-check',  'title' => 'Publicación gratuita',
         'desc' => 'No cobramos comisión ni cuota. Tus productos merecen visibilidad sin barreras.'],
        ['icon' => 'trending-up',  'title' => 'Crecé con nosotros',
         'desc' => 'Estamos construyendo algo juntos. Los primeros creadores serán parte de la historia de Tienda Moroni.'],
      ];
      foreach ($benefits as $b):
      ?>
      <div class="flex flex-col items-center text-center gap-4">
        <div class="w-14 h-14 rounded-full flex items-center justify-center flex-shrink-0"
             style="background:rgba(232,176,32,0.15)">
          <i data-lucide="<?= $b['icon'] ?>" class="w-7 h-7" style="color:var(--color-gold)"></i>
        </div>
        <div>
          <h3 class="font-serif font-bold text-white text-lg mb-2"
              style="font-family:'Playfair Display',Georgia,serif">
            <?= $b['title'] ?>
          </h3>
          <p class="text-sm leading-relaxed" style="color:rgba(255,255,255,0.70)">
            <?= $b['desc'] ?>
          </p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── Quote ─────────────────────────────────────────────────────────────────── -->
<section class="bg-warm-100 py-20">
  <div class="max-w-2xl mx-auto px-4 sm:px-6 text-center">
    <div class="w-16 h-1 rounded-full mx-auto mb-8" style="background:var(--color-gold)"></div>
    <blockquote class="font-serif text-2xl sm:text-3xl font-semibold italic leading-snug mb-5"
                style="font-family:'Playfair Display',Georgia,serif;color:var(--color-navy-deeper)">
      "Cada pieza que creás es una forma de compartir tu testimonio."
    </blockquote>
    <p class="text-warm-500 text-sm font-semibold tracking-wide">— Equipo de Tienda Moroni</p>
  </div>
</section>

<!-- ── Contact form ──────────────────────────────────────────────────────────── -->
<section id="formulario" class="bg-warm-50 py-20">
  <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

    <div class="text-center mb-10">
      <div class="flex items-center justify-center gap-3 mb-6">
        <span class="block h-px w-12" style="background:var(--color-gold);opacity:.5"></span>
        <i data-lucide="send" class="w-5 h-5" style="color:var(--color-gold)"></i>
        <span class="block h-px w-12" style="background:var(--color-gold);opacity:.5"></span>
      </div>
      <h2 class="font-serif text-3xl sm:text-4xl font-bold text-warm-900 mb-3"
          style="font-family:'Playfair Display',Georgia,serif">
        Contanos sobre vos y tus productos
      </h2>
      <p class="text-warm-500 text-sm">
        Completá el formulario y nos ponemos en contacto a la brevedad.
      </p>
    </div>

    <form method="POST" action="/publicar-gratis" class="space-y-5"
          x-data="{ submitting: false }" @submit="submitting = true">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

        <!-- Nombre -->
        <div>
          <label class="block text-sm font-semibold text-warm-700 mb-1" for="pub-name">
            Nombre <span class="text-red-400">*</span>
          </label>
          <input type="text" id="pub-name" name="name" required
                 value="<?= e($old['name'] ?? '') ?>"
                 class="w-full rounded-xl border px-4 py-2.5 text-sm text-warm-800
                        focus:outline-none focus:ring-2 transition
                        <?= isset($errors['name']) ? 'border-red-400 focus:ring-red-300 bg-red-50' : 'border-warm-300 focus:ring-gold' ?>">
          <?php if (isset($errors['name'])): ?>
            <p class="mt-1 text-red-500 text-xs"><?= e($errors['name']) ?></p>
          <?php endif; ?>
        </div>

        <!-- Apellido -->
        <div>
          <label class="block text-sm font-semibold text-warm-700 mb-1" for="pub-lastname">
            Apellido <span class="text-red-400">*</span>
          </label>
          <input type="text" id="pub-lastname" name="lastname" required
                 value="<?= e($old['lastname'] ?? '') ?>"
                 class="w-full rounded-xl border px-4 py-2.5 text-sm text-warm-800
                        focus:outline-none focus:ring-2 transition
                        <?= isset($errors['lastname']) ? 'border-red-400 focus:ring-red-300 bg-red-50' : 'border-warm-300 focus:ring-gold' ?>">
          <?php if (isset($errors['lastname'])): ?>
            <p class="mt-1 text-red-500 text-xs"><?= e($errors['lastname']) ?></p>
          <?php endif; ?>
        </div>

        <!-- Teléfono -->
        <div>
          <label class="block text-sm font-semibold text-warm-700 mb-1" for="pub-phone">
            Número de contacto <span class="text-red-400">*</span>
          </label>
          <input type="tel" id="pub-phone" name="phone" required
                 value="<?= e($old['phone'] ?? '') ?>"
                 placeholder="099 000 000"
                 class="w-full rounded-xl border px-4 py-2.5 text-sm text-warm-800
                        focus:outline-none focus:ring-2 transition
                        <?= isset($errors['phone']) ? 'border-red-400 focus:ring-red-300 bg-red-50' : 'border-warm-300 focus:ring-gold' ?>">
          <?php if (isset($errors['phone'])): ?>
            <p class="mt-1 text-red-500 text-xs"><?= e($errors['phone']) ?></p>
          <?php endif; ?>
        </div>

        <!-- Email -->
        <div>
          <label class="block text-sm font-semibold text-warm-700 mb-1" for="pub-email">
            Email <span class="text-red-400">*</span>
          </label>
          <input type="email" id="pub-email" name="email" required
                 value="<?= e($old['email'] ?? '') ?>"
                 class="w-full rounded-xl border px-4 py-2.5 text-sm text-warm-800
                        focus:outline-none focus:ring-2 transition
                        <?= isset($errors['email']) ? 'border-red-400 focus:ring-red-300 bg-red-50' : 'border-warm-300 focus:ring-gold' ?>">
          <?php if (isset($errors['email'])): ?>
            <p class="mt-1 text-red-500 text-xs"><?= e($errors['email']) ?></p>
          <?php endif; ?>
        </div>

      </div>

      <!-- Comentarios -->
      <div>
        <label class="block text-sm font-semibold text-warm-700 mb-1" for="pub-comments">
          ¿Qué productos creás? <span class="text-warm-400 font-normal">(opcional)</span>
        </label>
        <textarea id="pub-comments" name="comments" rows="4"
                  placeholder="Contanos sobre tus creaciones, materiales, inspiración..."
                  class="w-full rounded-xl border border-warm-300 px-4 py-2.5 text-sm text-warm-800
                         focus:outline-none focus:ring-2 focus:ring-gold transition resize-none"><?= e($old['comments'] ?? '') ?></textarea>
      </div>

      <!-- Submit -->
      <button type="submit"
              :disabled="submitting"
              class="w-full flex items-center justify-center gap-2 py-3 px-8 rounded-full
                     font-bold text-sm sm:text-base text-white transition duration-200
                     hover:opacity-90 disabled:opacity-60"
              style="background:var(--color-navy-deeper)">
        <template x-if="!submitting">
          <span class="flex items-center gap-2">
            <i data-lucide="send" class="w-4 h-4"></i>
            Enviar consulta
          </span>
        </template>
        <template x-if="submitting">
          <span class="flex items-center gap-2">
            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
            Enviando...
          </span>
        </template>
      </button>

    </form>
  </div>
</section>

<!-- Staggered card entrance via IntersectionObserver -->
<script>
(function () {
  const cards = document.querySelectorAll('#pub-cards .pub-card');
  if (!cards.length) return;
  const io = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        io.unobserve(entry.target);
      }
    });
  }, { threshold: 0.15 });
  cards.forEach(c => io.observe(c));
})();
</script>
