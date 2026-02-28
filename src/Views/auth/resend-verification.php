<?php $layout = 'layout/app'; ?>

<div class="min-h-[calc(100vh-200px)] flex items-center justify-center px-4 py-12">
  <div class="w-full max-w-md">

    <div class="text-center mb-8">
      <div class="flex justify-center mb-4">
        <div class="bg-brand-800/10 rounded-full p-4">
          <i data-lucide="mail-check" class="w-10 h-10 text-brand-800"></i>
        </div>
      </div>
      <h1 class="text-3xl font-bold text-warm-800" style="font-family:'Playfair Display',Georgia,serif">
        Reenviar link de verificación
      </h1>
      <p class="mt-2 text-warm-500 text-sm">
        Ingresá el email con el que te registraste.
      </p>
    </div>

    <?php if (!empty($success)): ?>
    <div class="mb-5 bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm flex items-start gap-2">
      <i data-lucide="check-circle" class="w-4 h-4 mt-0.5 shrink-0"></i>
      <?= e($success) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
    <div class="mb-5 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
      <?= e($error) ?>
    </div>
    <?php endif; ?>

    <div class="bg-white border border-warm-200 rounded-2xl p-8 shadow-sm"
         x-data="{ loading: false }">

      <form method="post" action="/auth/resend-verification"
            @submit="loading = true" class="space-y-4">
        <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">

        <div>
          <label class="block text-sm font-medium text-warm-700 mb-1">
            Email
          </label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
              <i data-lucide="mail" class="w-4 h-4 text-warm-400"></i>
            </span>
            <input type="email" name="email" required autocomplete="email"
                   value="<?= e(post('email', '')) ?>"
                   placeholder="tu@email.com"
                   class="w-full border border-warm-300 rounded-xl pl-10 pr-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
          </div>
        </div>

        <button type="submit"
                :disabled="loading"
                class="w-full bg-brand-800 text-white py-3 rounded-full font-bold text-sm hover:bg-brand-700 transition disabled:opacity-60 flex items-center justify-center gap-2">
          <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
          </svg>
          <span x-text="loading ? 'Enviando…' : 'Enviar nuevo link'">Enviar nuevo link</span>
        </button>
      </form>
    </div>

    <p class="text-center text-sm text-warm-400 mt-6">
      <a href="/auth/login" class="hover:text-brand-800 transition">
        ← Volver al inicio de sesión
      </a>
    </p>

  </div>
</div>
