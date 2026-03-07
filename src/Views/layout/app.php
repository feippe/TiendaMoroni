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
  <link rel="icon" href="/assets/img/isotipo.ico?v=2" type="image/x-icon">
  <link rel="shortcut icon" href="/assets/img/isotipo.ico?v=2" type="image/x-icon">
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

  <!-- WhatsApp flotante -->
  <style>
    .wa-float {
      position: fixed;
      bottom: 28px;
      right: 24px;
      z-index: 999;
      display: flex;
      align-items: center;
      flex-direction: row-reverse;
      gap: 10px;
      text-decoration: none;
    }

    /* Botón circular */
    .wa-float__btn {
      position: relative;
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 8px 32px rgba(37,211,102,.45), 0 2px 8px rgba(0,0,0,.15);
      transition: transform .25s cubic-bezier(.34,1.56,.64,1), box-shadow .25s ease;
      flex-shrink: 0;
    }

    /* Anillo de pulso */
    .wa-float__btn::before,
    .wa-float__btn::after {
      content: '';
      position: absolute;
      inset: 0;
      border-radius: 50%;
      background: #25D366;
      opacity: 0;
      animation: wa-pulse 2.6s ease-out infinite;
    }
    .wa-float__btn::after {
      animation-delay: 1.3s;
    }
    @keyframes wa-pulse {
      0%   { transform: scale(1);    opacity: .5; }
      80%  { transform: scale(1.75); opacity: 0;  }
      100% { transform: scale(1.75); opacity: 0;  }
    }

    .wa-float__btn svg {
      position: relative;
      z-index: 1;
      filter: drop-shadow(0 1px 2px rgba(0,0,0,.2));
    }

    /* Pill de texto */
    .wa-float__label {
      background: rgba(255,255,255,.97);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      color: #128C7E;
      font-size: 13px;
      font-weight: 700;
      letter-spacing: .01em;
      padding: 9px 16px;
      border-radius: 100px;
      box-shadow: 0 4px 20px rgba(0,0,0,.12), 0 0 0 1px rgba(37,211,102,.15);
      white-space: nowrap;
      opacity: 0;
      transform: translateX(8px);
      transition: opacity .28s ease, transform .28s cubic-bezier(.34,1.56,.64,1);
      pointer-events: none;
    }

    /* Hover */
    .wa-float:hover .wa-float__btn {
      transform: scale(1.1) rotate(-8deg);
      box-shadow: 0 12px 40px rgba(37,211,102,.6), 0 2px 8px rgba(0,0,0,.15);
    }
    .wa-float:hover .wa-float__label {
      opacity: 1;
      transform: translateX(0);
    }

    /* Mobile: sin label, botón levemente más grande */
    @media (max-width: 480px) {
      .wa-float { bottom: 20px; right: 16px; }
      .wa-float__label { display: none; }
    }
  </style>

  <a class="wa-float"
     href="https://wa.me/59895001480?text=Hola"
     target="_blank"
     rel="noopener noreferrer"
     aria-label="Ver catálogo en WhatsApp">
    <div class="wa-float__btn">
      <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="white">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.272-.099-.47-.148-.67.15-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.075-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.5-.669-.51-.173-.008-.372-.01-.571-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
        <path d="M12 0C5.373 0 0 5.373 0 12c0 2.127.558 4.122 1.528 5.855L.057 23.428a.75.75 0 0 0 .916.928l5.81-1.525A11.943 11.943 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.904a9.904 9.904 0 0 1-5.034-1.373l-.36-.214-3.733.979.997-3.64-.235-.374A9.904 9.904 0 1 1 12 21.904z"/>
      </svg>
    </div>
    <span class="wa-float__label">Ver catálogo en WhatsApp</span>
  </a>
</body>
</html>
