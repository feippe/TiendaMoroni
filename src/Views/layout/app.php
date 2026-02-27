<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title><?= e($pageTitle ?? SITE_NAME) ?></title>
  <meta name="description" content="<?= e($metaDesc ?? '') ?>">
  <link rel="canonical" href="<?= e($canonical ?? currentUrl()) ?>">
  <link rel="icon" href="/assets/img/isotipo.ico" type="image/x-icon">
  <link rel="shortcut icon" href="/assets/img/isotipo.ico" type="image/x-icon">

  <!-- Open Graph -->
  <?php if (isset($ogImage)): ?>
  <meta property="og:type"        content="product">
  <meta property="og:title"       content="<?= e($pageTitle ?? '') ?>">
  <meta property="og:description" content="<?= e($metaDesc ?? '') ?>">
  <meta property="og:image"       content="<?= e($ogImage) ?>">
  <meta property="og:url"         content="<?= e($canonical ?? currentUrl()) ?>">
  <?php endif; ?>

  <!-- Google Fonts: Playfair Display (títulos) + Lato (cuerpo) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">

  <!-- Tailwind CSS CDN — load CDN first, then configure -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans:  ['Lato', 'sans-serif'],
            serif: ['Playfair Display', 'Georgia', 'serif'],
          },
          colors: {
            brand: {
              50:  '#f5f7fa',
              100: '#e8edf4',
              200: '#c8d4e4',
              300: '#9ab1cc',
              400: '#C6A75E',   /* gold matte — primary accent */
              500: '#b5923f',
              600: '#1B3A5C',   /* mid navy — compatibility */
              700: '#162E4A',   /* mid navy */
              800: '#0F1E2E',   /* deep midnight navy — primary */
              900: '#090f17',
            },
            warm: {
              50:  '#F8F6F2',   /* warm white */
              100: '#f0ede6',
              200: '#e2ddd4',
              300: '#cdc7bb',
              400: '#a8a092',
              500: '#7a7268',
              600: '#5a5450',
              700: '#44403c',
              800: '#2C2A27',   /* dark text */
              900: '#1a1916',
            },
            accent: {
              DEFAULT: '#C6A75E', /* gold matte */
              light:   '#d4b97a',
              dark:    '#a88840',
            },
          },
          transitionDuration: { DEFAULT: '200ms' },
        }
      }
    }
  </script>

  <!-- Alpine.js plugins (must load before Alpine) -->
  <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/intersect@3.x.x/dist/cdn.min.js"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
  <!-- Alpine.js CDN -->
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <!-- Cart seed for Alpine store (inline so it runs before alpine:init) -->
  <script>var __CART_COUNT = <?= (int)\TiendaMoroni\Core\Cart::count() ?>;</script>

  <!-- Custom styles -->
  <link rel="stylesheet" href="/assets/css/app.css">

  <?php if (isset($jsonLD)): ?>
  <script type="application/ld+json"><?= $jsonLD ?></script>
  <?php endif; ?>
</head>
<body class="bg-warm-50 text-warm-900 font-sans antialiased" x-data="{ mobileMenuOpen: false }">

  <!-- Navigation -->
  <?php echo partial('partials/nav') ?>

  <!-- Flash messages -->
  <?php $flash = \TiendaMoroni\Core\Session::getFlash('success'); ?>
  <?php if ($flash): ?>
  <div class="fixed top-20 right-4 z-50 bg-green-500 text-white px-5 py-3 rounded-lg shadow-lg text-sm"
       x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
       x-transition>
    <?= e($flash) ?>
  </div>
  <?php endif; ?>

  <!-- Main content -->
  <main>
    <?= $content ?? '' ?>
  </main>

  <!-- Footer -->
  <?php echo partial('partials/footer') ?>

  <!-- Cart notification toast -->
  <div x-cloak x-show="$store.cart.toast"
       x-transition
       class="fixed bottom-6 right-6 z-50 bg-brand-800 text-white px-5 py-3 rounded-xl shadow-xl text-sm font-medium">
    ¡Agregado al carrito! 🛒
  </div>

  <script src="/assets/js/app.js"></script>
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
  <script>document.addEventListener('DOMContentLoaded', () => lucide.createIcons());</script>
</body>
</html>
