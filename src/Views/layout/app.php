<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title><?= e($pageTitle ?? SITE_NAME) ?></title>
  <meta name="description" content="<?= e($metaDesc ?? '') ?>">
  <?php if (!empty($noindex)): ?>
  <meta name="robots" content="noindex, nofollow">
  <?php endif; ?>
  <link rel="canonical" href="<?= e($canonical ?? currentUrl()) ?>">
  <link rel="icon" href="/assets/img/isotipo.ico" type="image/x-icon">
  <link rel="shortcut icon" href="/assets/img/isotipo.ico" type="image/x-icon">
  <link rel="preload" as="image" href="/assets/img/hero.webp" fetchpriority="high">
  <meta name="theme-color" content="#ffffff">

  <!-- Open Graph -->
  <meta property="og:site_name"    content="<?= e(SITE_NAME) ?>">
  <meta property="og:type"         content="<?= e($ogType ?? 'website') ?>">
  <meta property="og:title"        content="<?= e($pageTitle ?? SITE_NAME) ?>">
  <meta property="og:description"  content="<?= e($metaDesc ?? '') ?>">
  <meta property="og:image"        content="<?= e($ogImage ?? SITE_URL . '/assets/img/hero.webp') ?>">
  <meta property="og:url"          content="<?= e($canonical ?? currentUrl()) ?>">

  <!-- Twitter Cards -->
  <meta name="twitter:card"        content="summary_large_image">
  <meta name="twitter:title"       content="<?= e($pageTitle ?? SITE_NAME) ?>">
  <meta name="twitter:description" content="<?= e($metaDesc ?? '') ?>">
  <meta name="twitter:image"       content="<?= e($ogImage ?? SITE_URL . '/assets/img/hero.webp') ?>">

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
            /* ── Sistema de diseño ── */
            gold: {
              DEFAULT: '#E8B020',   /* primario */
              dark:    '#C8920A',   /* hover primario */
              soft:    '#F5C842',   /* badges */
              light:   '#FEF9EC',
              tint:    '#FDF0C4',
              deeper:  '#9E6F05',
            },
            navy: {
              DEFAULT: '#1E3A6E',   /* secundario */
              dark:    '#152B54',   /* hover / footer */
              deeper:  '#0D1C38',   /* dark bg / texto oscuro */
              light:   '#EEF1F8',   /* superficie clara */
              mid:     '#4F6EA8',   /* texto mutado */
            },
            /* ── Neutrales ── */
            warm: {
              50:  '#F8F6F2',
              100: '#f0ede6',
              200: '#e2ddd4',
              300: '#cdc7bb',
              400: '#7c756c', /* ≥4.5:1 vs blanco — pasa WCAG AA */
              500: '#7a7268',
              600: '#5a5450',
              700: '#44403c',
              800: '#2C2A27',
              900: '#1a1916',
            },
            /* ── Aliases de compatibilidad — mapean a paleta oficial ── */
            brand: {
              50:  '#F8F9FA',   /* --color-bg */
              100: '#EEF1F8',   /* --color-navy-light */
              200: '#EEF1F8',   /* --color-navy-light */
              300: '#4F6EA8',   /* --color-navy-mid */
              400: '#E8B020',   /* --color-gold */
              500: '#C8920A',   /* --color-gold-dark */
              600: '#1E3A6E',   /* --color-navy */
              700: '#152B54',   /* --color-navy-dark */
              800: '#0D1C38',   /* --color-navy-deeper */
              900: '#0D1C38',   /* --color-navy-deeper */
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
       class="fixed bottom-6 right-6 z-50 bg-navy text-white px-5 py-3 rounded-xl shadow-xl text-sm font-medium">
    ¡Agregado al carrito! 🛒
  </div>

  <script src="/assets/js/app.js"></script>
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
  <script>document.addEventListener('DOMContentLoaded', () => lucide.createIcons());</script>
</body>
</html>
