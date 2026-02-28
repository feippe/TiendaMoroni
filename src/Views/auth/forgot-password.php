<?php $layout = 'layout/app'; ?>

<div class="min-h-[calc(100vh-200px)] flex items-center justify-center px-4 py-16">
  <div class="w-full max-w-md">

    <!-- Header -->
    <div class="text-center mb-8">
      <h1 class="text-3xl font-bold text-warm-800 mb-2" style="font-family:'Playfair Display',Georgia,serif">
        ¿Olvidaste tu contraseña?
      </h1>
      <p class="text-warm-400 text-sm">
        Ingresá tu email y te enviaremos un link para crear una nueva.
      </p>
    </div>

    <?php if (!empty($error)): ?>
    <div class="mb-5 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm flex items-start gap-2">
      <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
      <?= e($error) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
    <!-- Success message -->
    <div class="mb-5 bg-warm-100 border border-warm-200 rounded-xl p-4 text-sm text-warm-700 flex items-start gap-3">
      <i data-lucide="mail-check" class="w-5 h-5 mt-0.5 flex-shrink-0" style="color:#C6A75E"></i>
      <div>
        <p class="font-medium mb-0.5">Email enviado</p>
        <p><?= e($success) ?> Revisá también tu carpeta de spam.</p>
      </div>
    </div>
    <?php endif; ?>

    <!-- Form -->
    <div x-data="{ loading: false }" class="bg-white border border-warm-200 rounded-2xl shadow-sm p-8">
      <form method="post" action="/auth/forgot-password" @submit="loading = true">
        <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">

        <div class="mb-6">
          <label class="block text-sm font-medium text-warm-700 mb-1.5">Email</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-warm-400">
              <i data-lucide="mail" class="w-4 h-4"></i>
            </span>
            <input type="email" name="email" required autocomplete="email"
                   placeholder="tu@email.com"
                   class="w-full pl-10 pr-4 py-3 border border-warm-300 rounded-xl text-sm
                          focus:outline-none focus:ring-2 transition"
                   style="--tw-ring-color:#C6A75E">
          </div>
        </div>

        <button type="submit"
                :disabled="loading"
                :class="loading ? 'opacity-70 cursor-not-allowed' : 'hover:opacity-90'"
                class="w-full flex items-center justify-center gap-2 py-3 rounded-full font-bold text-sm transition"
                style="background:#0F1E2E;color:#F8F6F2">
          <span x-show="!loading">Enviar link de reseteo</span>
          <span x-show="loading" class="flex items-center gap-2" x-cloak>
            <i data-lucide="loader" class="w-4 h-4 animate-spin"></i>
            Enviando…
          </span>
        </button>
      </form>
    </div>

    <!-- Back link -->
    <p class="mt-6 text-center text-sm text-warm-400">
      <a href="/auth/login" class="hover:text-brand-800 transition flex items-center justify-center gap-1">
        <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
        Volver al inicio de sesión
      </a>
    </p>

  </div>
</div>
