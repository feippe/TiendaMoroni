<?php $layout = 'layout/app'; ?>

<div class="min-h-[calc(100vh-280px)] bg-warm-50 flex items-center justify-center px-4 py-20">
  <div class="w-full max-w-2xl text-center">

    <!-- Decorative top ornament -->
    <div class="flex items-center justify-center gap-3 mb-6">
      <span class="block h-px w-16 bg-navy-mid opacity-60"></span>
      <i data-lucide="triangle-alert" class="w-5 h-5 text-gold"></i>
      <span class="block h-px w-16 bg-navy-mid opacity-60"></span>
    </div>

    <!-- 404 number with layered style -->
    <div class="relative inline-block select-none mb-4">
      <span class="absolute inset-0 text-[9rem] font-black text-navy-light translate-x-1 translate-y-1 leading-none" aria-hidden="true">404</span>
      <span class="relative text-[9rem] font-black leading-none" style="
        background: linear-gradient(135deg, var(--color-navy-dark) 0%, var(--color-gold) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
      ">404</span>
    </div>

    <!-- Heading -->
    <h1 class="text-3xl sm:text-4xl font-bold text-warm-900 leading-tight" style="font-family:'Playfair Display',Georgia,serif">
      Esta página no existe
    </h1>

    <!-- Subtext -->
    <p class="mt-4 text-warm-500 text-base max-w-sm mx-auto leading-relaxed">
      Quizás el enlace expiró, el producto fue dado de baja, o la URL tiene un error tipográfico.
    </p>

    <!-- Divider with icon -->
    <div class="flex items-center justify-center gap-3 my-8">
      <span class="block h-px w-24 bg-warm-200"></span>
      <i data-lucide="package-search" class="w-6 h-6 text-warm-300"></i>
      <span class="block h-px w-24 bg-warm-200"></span>
    </div>

    <!-- Action buttons -->
    <div class="flex flex-col sm:flex-row gap-3 justify-center">
      <a href="/"
         class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-navy text-white rounded-xl font-semibold text-sm hover:bg-navy-dark transition shadow-sm">
        <i data-lucide="home" class="w-4 h-4"></i>
        Volver al inicio
      </a>
      <a href="/productos"
         class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-white border border-warm-300 text-warm-700 rounded-xl font-semibold text-sm hover:bg-warm-100 transition shadow-sm">
        <i data-lucide="grid-2x2" class="w-4 h-4"></i>
        Explorar productos
      </a>
      <a href="/categorias"
         class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-white border border-warm-300 text-warm-700 rounded-xl font-semibold text-sm hover:bg-warm-100 transition shadow-sm">
        <i data-lucide="tag" class="w-4 h-4"></i>
        Ver categorías
      </a>
    </div>

    <!-- Quick links -->
    <p class="mt-10 text-xs text-warm-400 uppercase tracking-widest font-semibold">También podés ir a</p>
    <div class="mt-3 flex flex-wrap items-center justify-center gap-x-5 gap-y-2">
      <a href="/auth/login"    class="text-sm text-warm-500 hover:text-navy transition flex items-center gap-1">
        <i data-lucide="log-in" class="w-3.5 h-3.5"></i> Iniciar sesión
      </a>
      <span class="text-warm-200">|</span>
      <a href="/carrito"       class="text-sm text-warm-500 hover:text-navy transition flex items-center gap-1">
        <i data-lucide="shopping-cart" class="w-3.5 h-3.5"></i> Mi carrito
      </a>
      <span class="text-warm-200">|</span>
      <a href="/mi-cuenta"     class="text-sm text-warm-500 hover:text-navy transition flex items-center gap-1">
        <i data-lucide="user" class="w-3.5 h-3.5"></i> Mi cuenta
      </a>
    </div>

    <!-- Bottom ornament -->
    <div class="flex items-center justify-center gap-3 mt-12">
      <span class="block h-px w-16 bg-navy-mid opacity-40"></span>
      <span class="text-gold text-lg" aria-hidden="true">✦</span>
      <span class="block h-px w-16 bg-navy-mid opacity-40"></span>
    </div>

  </div>
</div>
