<nav class="sticky top-0 z-40 bg-navy border-b border-navy-dark shadow-sm">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-16">

      <!-- Logo -->
      <a href="/" class="flex items-center">
        <img src="/assets/img/Logo-Dark.svg?v=2"
             alt="<?= e(SITE_NAME) ?>"
             class="h-9 w-auto"
             height="32">
      </a>

      <!-- Search (desktop) -->
      <form action="/buscar" method="get" role="search" aria-label="Buscar en <?= e(SITE_NAME) ?>"
            class="hidden md:flex flex-1 max-w-md mx-8">
        <div class="relative w-full">
          <input type="search" name="q" placeholder="Buscá productos, regalos, accesorios..."
                 aria-label="Buscar productos"
                 value="<?= e(get('q','')) ?>"
                 class="w-full pl-4 pr-10 py-2 bg-white/10 border border-white/20 rounded-lg text-sm text-white
                        placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent
                        transition">
          <button type="submit" aria-label="Enviar búsqueda"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-white/60 hover:text-gold transition">
            <i data-lucide="search" class="w-4 h-4" aria-hidden="true"></i>
          </button>
        </div>
      </form>

      <!-- Desktop actions -->
      <div class="hidden md:flex items-center gap-4">
        <!-- Cart -->
        <a id="cart-icon" href="/carrito" aria-label="Ver carrito"
           class="relative flex items-center text-navy-light hover:text-gold transition">
          <i data-lucide="shopping-cart" class="w-6 h-6"></i>
          <span x-show="$store.cart.count > 0"
                x-text="$store.cart.count"
                style="<?= \TiendaMoroni\Core\Cart::count() > 0 ? '' : 'display:none' ?>"
                class="absolute -top-2 -right-2 bg-gold text-navy-deeper text-xs font-bold
                       min-w-[20px] h-5 rounded-full flex items-center justify-center px-1 leading-none">
          </span>
        </a>

        <!-- Account -->
        <?php if (\TiendaMoroni\Core\Session::isLoggedIn()): ?>
        <div class="relative" x-data="{ open: false }">
          <button @click="open = !open" class="flex items-center gap-2 text-sm font-medium text-navy-light hover:text-gold transition">
            <?php $u = \TiendaMoroni\Core\Session::user(); ?>
            <?php if ($u['avatar_url']): ?>
            <img src="<?= e($u['avatar_url']) ?>" alt="" class="w-7 h-7 rounded-full object-cover">
            <?php else: ?>
            <div class="w-7 h-7 rounded-full bg-white/20 flex items-center justify-center text-white font-bold text-xs">
              <?= strtoupper(substr($u['name'], 0, 1)) ?>
            </div>
            <?php endif; ?>
            <?= e(explode(' ', $u['name'])[0]) ?>
            <span :class="open && 'rotate-180'" class="transition-transform duration-200 inline-flex">
              <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
            </span>
          </button>
          <div x-show="open" @click.outside="open = false" x-transition
               class="absolute right-0 mt-2 w-44 bg-white border border-warm-200 rounded-xl shadow-lg py-1 text-sm">
            <a href="/mi-cuenta" class="block px-4 py-2 text-warm-700 hover:bg-warm-50 transition">Mi cuenta</a>
            <?php if (\TiendaMoroni\Core\Session::isAdmin()): ?>
            <a href="/admin" class="block px-4 py-2 text-warm-700 hover:bg-warm-50 transition">Admin</a>
            <?php endif; ?>
            <hr class="my-1 border-warm-100">
            <a href="/auth/logout" class="block px-4 py-2 text-red-500 hover:bg-red-50 transition">Cerrar sesión</a>
          </div>
        </div>
        <?php else: ?>
        <a href="/auth/login"
           class="text-sm font-medium text-navy-light hover:text-gold transition">
          Iniciar sesión
        </a>
        <a href="/auth/register"
           class="text-sm font-semibold bg-gold text-navy-deeper px-4 py-2 rounded-lg hover:bg-gold-dark transition">
          Registrate
        </a>
        <?php endif; ?>
      </div>

      <!-- Mobile hamburger -->
      <button @click="mobileMenuOpen = !mobileMenuOpen"
              :aria-expanded="mobileMenuOpen.toString()"
              aria-controls="mobile-menu"
              aria-label="Abrir menú"
              class="md:hidden text-navy-light">
        <span x-show="!mobileMenuOpen" aria-hidden="true"><i data-lucide="menu" class="w-6 h-6"></i></span>
        <span x-show="mobileMenuOpen"  aria-hidden="true"><i data-lucide="x" class="w-6 h-6"></i></span>
      </button>

    </div>

    <!-- Mobile menu -->
    <div id="mobile-menu" x-show="mobileMenuOpen" x-transition class="md:hidden pb-4">
      <form action="/buscar" method="get" role="search" aria-label="Buscar en <?= e(SITE_NAME) ?>" class="mb-3">
        <input type="search" name="q" placeholder="Buscar productos..."
               aria-label="Buscar productos"
               class="w-full px-4 py-2 bg-white/10 border border-white/20 rounded-lg text-sm text-white
                      placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-gold transition">
      </form>
      <div class="flex flex-col gap-1 text-sm font-medium">
        <a href="/productos" class="py-2 text-navy-light">Productos</a>
        <a href="/carrito" class="py-2 text-navy-light">Carrito (<span x-text="$store.cart.count"><?= \TiendaMoroni\Core\Cart::count() ?></span>)</a>
        <?php if (\TiendaMoroni\Core\Session::isLoggedIn()): ?>
        <a href="/mi-cuenta" class="py-2 text-navy-light">Mi cuenta</a>
        <?php if (\TiendaMoroni\Core\Session::isAdmin()): ?>
        <a href="/admin" class="py-2 text-navy-light">Admin</a>
        <?php endif; ?>
        <a href="/auth/logout" class="py-2 text-red-400">Cerrar sesión</a>
        <?php else: ?>
        <a href="/auth/login"    class="py-2 text-navy-light">Iniciar sesión</a>
        <a href="/auth/register" class="py-2 text-gold font-semibold">Registrate</a>
        <?php endif; ?>
      </div>
    </div>

  </div>
</nav>

<!-- Category bar (desktop) -->
<div class="hidden md:block bg-navy-dark border-b border-navy">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center gap-6 h-10 text-sm font-medium text-navy-light/80 overflow-x-auto">
      <a href="/productos" class="hover:text-gold whitespace-nowrap transition">Todos los productos</a>
      <?php foreach (\TiendaMoroni\Models\CategoryModel::roots() as $cat): ?>
      <a href="/categoria/<?= e($cat['slug']) ?>"
         class="hover:text-gold whitespace-nowrap transition">
        <?= e($cat['name']) ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
