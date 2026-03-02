<?php $layout = 'layout/app'; ?>

<div class="min-h-[calc(100vh-200px)] flex items-center justify-center px-4 py-16">
  <div class="w-full max-w-md">

    <!-- Header -->
    <div class="text-center mb-8">
      <h1 class="text-3xl font-bold text-warm-800 mb-2" style="font-family:'Playfair Display',Georgia,serif">
        Creá tu nueva contraseña
      </h1>
      <p class="text-warm-400 text-sm">
        Tu link es válido por 60 minutos desde que lo solicitaste.
      </p>
    </div>

    <?php if (!empty($error)): ?>
    <div class="mb-5 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm flex items-start gap-2">
      <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
      <?= e($error) ?>
    </div>
    <?php endif; ?>

    <!-- Form -->
    <div x-data="{
           showPass: false,
           showConfirm: false,
           password: '',
           confirm: '',
           get valid() { return this.password.length >= 8 && this.password === this.confirm; },
           get mismatch() { return this.confirm.length > 0 && this.password !== this.confirm; },
           loading: false
         }"
         class="bg-white border border-warm-200 rounded-2xl shadow-sm p-8">

      <form method="post" action="/auth/reset-password" @submit="loading = true">
        <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
        <input type="hidden" name="token" value="<?= e($token ?? '') ?>">

        <!-- New password -->
        <div class="mb-5">
          <label class="block text-sm font-medium text-warm-700 mb-1.5">Nueva contraseña</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-warm-400">
              <i data-lucide="lock" class="w-4 h-4"></i>
            </span>
            <input :type="showPass ? 'text' : 'password'"
                   name="password"
                   x-model="password"
                   required
                   minlength="8"
                   autocomplete="new-password"
                   class="w-full pl-10 pr-10 py-3 border rounded-xl text-sm focus:outline-none focus:ring-2 transition
                          <?= !empty($errors['password']) ? 'border-red-400 bg-red-50' : 'border-warm-300' ?>"
                   style="--tw-ring-color:var(--color-gold)">
            <button type="button" @click="showPass = !showPass"
                    class="absolute inset-y-0 right-3 flex items-center text-warm-400 hover:text-warm-600">
              <i data-lucide="eye"     class="w-4 h-4" x-show="!showPass"></i>
              <i data-lucide="eye-off" class="w-4 h-4" x-show="showPass" x-cloak></i>
            </button>
          </div>
          <?php if (!empty($errors['password'])): ?>
          <p class="mt-1 text-xs text-red-500"><?= e($errors['password']) ?></p>
          <?php else: ?>
          <p class="mt-1 text-xs text-warm-400">Mínimo 8 caracteres</p>
          <?php endif; ?>
        </div>

        <!-- Confirm password -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-warm-700 mb-1.5">Confirmá tu contraseña</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-warm-400">
              <i data-lucide="lock" class="w-4 h-4"></i>
            </span>
            <input :type="showConfirm ? 'text' : 'password'"
                   name="password_confirm"
                   x-model="confirm"
                   required
                   autocomplete="new-password"
                   class="w-full pl-10 pr-10 py-3 border rounded-xl text-sm focus:outline-none focus:ring-2 transition border-warm-300"
                   :class="mismatch ? 'border-red-400 bg-red-50' : '<?= !empty($errors['password_confirm']) ? 'border-red-400 bg-red-50' : '' ?>'"
                   style="--tw-ring-color:var(--color-gold)">
            <button type="button" @click="showConfirm = !showConfirm"
                    class="absolute inset-y-0 right-3 flex items-center text-warm-400 hover:text-warm-600">
              <i data-lucide="eye"     class="w-4 h-4" x-show="!showConfirm"></i>
              <i data-lucide="eye-off" class="w-4 h-4" x-show="showConfirm" x-cloak></i>
            </button>
          </div>
          <div class="mt-1 min-h-[16px]">
            <p x-show="mismatch" class="text-xs text-red-500" x-cloak>Las contraseñas no coinciden.</p>
            <p x-show="valid" class="text-xs text-green-600 flex items-center gap-1" x-cloak>
              <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Las contraseñas coinciden
            </p>
            <?php if (!empty($errors['password_confirm'])): ?>
            <p class="text-xs text-red-500"><?= e($errors['password_confirm']) ?></p>
            <?php endif; ?>
          </div>
        </div>

        <button type="submit"
                :disabled="!valid || loading"
                :class="(!valid || loading) ? 'opacity-50 cursor-not-allowed' : 'hover:opacity-90'"
                class="w-full flex items-center justify-center gap-2 py-3 rounded-full font-bold text-sm transition"
                style="background:var(--color-navy-deeper);color:var(--color-white)">
          <span x-show="!loading">Guardar nueva contraseña</span>
          <span x-show="loading" class="flex items-center gap-2" x-cloak>
            <i data-lucide="loader" class="w-4 h-4 animate-spin"></i>
            Guardando…
          </span>
        </button>
      </form>
    </div>

    <p class="mt-6 text-center text-sm text-warm-400">
      <a href="/auth/forgot-password" class="hover:text-navy transition flex items-center justify-center gap-1">
        <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
        Solicitá un nuevo link
      </a>
    </p>

  </div>
</div>
