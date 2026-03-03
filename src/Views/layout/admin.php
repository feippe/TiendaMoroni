<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'Admin – ' . SITE_NAME) ?></title>
  <link rel="icon" href="/assets/img/isotipo.ico" type="image/x-icon">
  <link rel="shortcut icon" href="/assets/img/isotipo.ico" type="image/x-icon">
  <meta name="robots" content="noindex, nofollow">
  <meta name="theme-color" content="#0D1C38">
  <meta name="csrf-token" content="<?= csrfToken() ?>">
  <style>[x-cloak]{display:none!important}</style>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/app.css">

  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Lato', 'sans-serif'], serif: ['Playfair Display', 'Georgia', 'serif'] },
          colors: {
            gold: {
              light:  '#FEF9EC',
              tint:   '#FDF0C4',
              soft:   '#F5C842',
              DEFAULT:'#E8B020',
              dark:   '#C8920A',
              deeper: '#9E6F05',
            },
            navy: {
              light:  '#EEF1F8',
              mid:    '#4F6EA8',
              DEFAULT:'#1E3A6E',
              dark:   '#152B54',
              deeper: '#0D1C38',
            },
            /* Aliases de compatibilidad — mapean a paleta oficial */
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
            accent: {
              DEFAULT: '#E8B020',   /* --color-gold */
              light:   '#F5C842',   /* --color-gold-soft */
              dark:    '#C8920A',   /* --color-gold-dark */
            },
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
          }
        }
      }
    }
  </script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <script>
    document.addEventListener('alpine:init', () => {
      <?php
        try { $__maint = \TiendaMoroni\Models\SettingModel::get('maintenance_mode') === '1'; }
        catch (\Throwable) { $__maint = false; }
      ?>
      Alpine.store('siteStatus', { maintenance: <?= $__maint ? 'true' : 'false' ?> });
    });
  </script>
</head>
<body class="bg-warm-100 font-sans antialiased text-warm-900" x-data="{ sidebarOpen: true }">

<div class="flex h-screen overflow-hidden">

  <!-- Sidebar -->
  <aside :class="sidebarOpen ? 'w-56' : 'w-14'"
         class="flex-shrink-0 text-warm-200 flex flex-col transition-all duration-200 overflow-hidden"
         style="background:#0D1C38;border-right:1px solid rgba(232,176,32,0.12)">

    <!-- Logo -->
    <div class="h-16 flex items-center px-4 border-b border-warm-700">
      <a href="/admin" class="flex items-center gap-2 font-bold text-white truncate">
        <img src="/assets/img/isotipo.svg"
             alt="Tienda Moroni"
             class="w-7 h-7 flex-shrink-0"
             width="28" height="28">
        <span x-show="sidebarOpen" class="text-sm truncate">Admin Panel</span>
      </a>
    </div>

    <!-- Nav links -->
    <nav class="flex-1 py-4 overflow-y-auto">
      <?php
        use TiendaMoroni\Models\SettingModel;
        try { $maintenanceOn = SettingModel::get('maintenance_mode') === '1'; }
        catch (\Throwable) { $maintenanceOn = false; }
        $navItems = [
          ['href'=>'/admin',               'label'=>'Dashboard',     'badge'=>null,                        'icon'=>'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
          ['href'=>'/admin/productos',     'label'=>'Productos',     'badge'=>null,                        'icon'=>'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
          ['href'=>'/admin/categorias',    'label'=>'Categorías',    'badge'=>null,                        'icon'=>'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
          ['href'=>'/admin/pedidos',       'label'=>'Pedidos',       'badge'=>null,                        'icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
          ['href'=>'/admin/usuarios',      'label'=>'Usuarios',      'badge'=>null,                        'icon'=>'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
          ['href'=>'/admin/preguntas',     'label'=>'Preguntas',     'badge'=>null,                        'icon'=>'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
          ['href'=>'/admin/vendedores',    'label'=>'Vendedores',    'badge'=>null,                        'icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
          ['href'=>'/admin/repositorio',   'label'=>'Repositorio',   'badge'=>null,                        'icon'=>'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
          ['href'=>'/admin/configuracion', 'label'=>'Configuración', 'badge'=>null, 'statusBadge'=>true, 'icon'=>'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
        ];
        $current = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
      ?>
      <?php foreach ($navItems as $item): ?>
      <?php $active = ($item['href'] === '/admin' ? $current === '/admin' : str_starts_with($current, $item['href'])); ?>
      <a href="<?= $item['href'] ?>"
         class="flex items-center gap-3 px-4 py-2.5 text-sm transition relative"
         style="<?= $active ? 'background:rgba(232,176,32,0.12);color:#E8B020;border-left:3px solid #E8B020;padding-left:13px' : 'color:#8899AA' ?>"
         onmouseover="if(!<?= $active ? 'true' : 'false' ?>){this.style.background='rgba(255,255,255,0.05)';this.style.color='#fff'}"
         onmouseout="if(!<?= $active ? 'true' : 'false' ?>){this.style.background='transparent';this.style.color='#8899AA'}">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="<?= $item['icon'] ?>"/>
        </svg>
        <span x-show="sidebarOpen" class="truncate flex-1"><?= $item['label'] ?></span>
        <?php if (!empty($item['badge'])): ?>
        <span x-show="sidebarOpen" class="ml-auto text-xs font-bold px-1.5 py-0.5 rounded" style="background:rgba(232,176,32,0.2);color:var(--color-gold)"><?= e($item['badge']) ?></span>
        <?php elseif (!empty($item['statusBadge'])): ?>
        <span x-show="sidebarOpen"
              class="ml-auto text-xs font-bold px-1.5 py-0.5 rounded transition-colors"
              :class="$store.siteStatus.maintenance ? 'bg-red-500/20 text-red-400' : 'bg-green-500/20 text-green-500'"
              x-text="$store.siteStatus.maintenance ? 'Offline' : 'Online'"></span>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </nav>

    <!-- Footer -->
    <div class="border-t border-warm-700 p-4">
      <a href="/auth/logout" class="flex items-center gap-2 text-xs text-warm-400 hover:text-red-400 transition">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
        </svg>
        <span x-show="sidebarOpen">Salir</span>
      </a>
    </div>
  </aside>

  <!-- Main area -->
  <div class="flex-1 flex flex-col overflow-hidden">

    <!-- Topbar -->
    <header class="h-16 bg-white border-b border-warm-200 flex items-center gap-4 px-6 flex-shrink-0">
      <button @click="sidebarOpen = !sidebarOpen" class="text-warm-500 hover:text-warm-900 transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
      </button>
      <span class="text-sm font-semibold text-warm-700"><?= e($pageTitle ?? '') ?></span>
      <div class="ml-auto flex items-center gap-3 text-sm text-warm-500">
        <a href="/" target="_blank" class="hover:text-brand-800 transition">Ver sitio →</a>
        <?php $u = \TiendaMoroni\Core\Session::user(); ?>
        <span class="font-medium text-warm-900"><?= e($u['name'] ?? '') ?></span>
      </div>
    </header>

    <!-- Page content -->
    <main class="flex-1 overflow-y-auto p-6">
      <?php if (!empty($successMsg)): ?>
      <div class="mb-4 bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">
        <?= e($successMsg) ?>
      </div>
      <?php endif; ?>
      <?php if (!empty($error)): ?>
      <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
        <?= e($error) ?>
      </div>
      <?php endif; ?>
      <?= $content ?? '' ?>
    </main>

  </div>
</div>

<script src="/assets/js/admin.js"></script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
<script>document.addEventListener('DOMContentLoaded', () => lucide.createIcons());</script>
<?php echo partial('partials/admin/media_picker_modal') ?>
</body>
</html>
