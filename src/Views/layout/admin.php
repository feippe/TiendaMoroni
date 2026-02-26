<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'Admin – ' . SITE_NAME) ?></title>
  <meta name="robots" content="noindex, nofollow">
  <meta name="csrf-token" content="<?= csrfToken() ?>">
  <style>[x-cloak]{display:none!important}</style>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Lato', 'sans-serif'], serif: ['Playfair Display', 'Georgia', 'serif'] },
          colors: {
            brand: {
              50:  '#f7f0d6',
              100: '#f0e4b0',
              200: '#e0ca78',
              400: '#C9A84C',
              700: '#1B3A5C',
              800: '#142d47',
              900: '#0e1e30',
            },
            warm: { 50:'#FAFAF7',100:'#f5f5f0',200:'#e7e5e0',300:'#d6d3ce',500:'#78716c',700:'#44403c',900:'#1c1917' }
          }
        }
      }
    }
  </script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-warm-100 font-sans antialiased text-warm-900" x-data="{ sidebarOpen: true }">

<div class="flex h-screen overflow-hidden">

  <!-- Sidebar -->
  <aside :class="sidebarOpen ? 'w-56' : 'w-14'"
         class="flex-shrink-0 bg-warm-900 text-warm-200 flex flex-col transition-all duration-200 overflow-hidden">

    <!-- Logo -->
    <div class="h-16 flex items-center px-4 border-b border-warm-700">
      <a href="/admin" class="flex items-center gap-2 font-bold text-white truncate">
        <svg class="w-6 h-6 flex-shrink-0" viewBox="0 0 24 24" fill="none">
          <rect width="24" height="24" rx="6" fill="#1B3A5C"/>
          <path d="M5 7h14M5 12h9M5 17h11" stroke="#C9A84C" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <span x-show="sidebarOpen" class="text-sm">Admin Panel</span>
      </a>
    </div>

    <!-- Nav links -->
    <nav class="flex-1 py-4 overflow-y-auto">
      <?php
        $navItems = [
          ['href'=>'/admin',           'label'=>'Dashboard',  'icon'=>'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
          ['href'=>'/admin/productos', 'label'=>'Productos',  'icon'=>'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
          ['href'=>'/admin/categorias','label'=>'Categorías', 'icon'=>'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
          ['href'=>'/admin/pedidos',   'label'=>'Pedidos',    'icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
          ['href'=>'/admin/preguntas', 'label'=>'Preguntas',  'icon'=>'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
          ['href'=>'/admin/vendedores','label'=>'Vendedores',  'icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
        ];
        $current = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
      ?>
      <?php foreach ($navItems as $item): ?>
      <?php $active = ($item['href'] === '/admin' ? $current === '/admin' : str_starts_with($current, $item['href'])); ?>
      <a href="<?= $item['href'] ?>"
         class="flex items-center gap-3 px-4 py-2.5 text-sm transition
                <?= $active ? 'bg-brand-700 text-white' : 'text-warm-300 hover:bg-warm-700 hover:text-white' ?>">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="<?= $item['icon'] ?>"/>
        </svg>
        <span x-show="sidebarOpen" class="truncate"><?= $item['label'] ?></span>
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
        <a href="/" target="_blank" class="hover:text-brand-700 transition">Ver sitio →</a>
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
