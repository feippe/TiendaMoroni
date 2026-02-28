<?php $layout = 'layout/app'; ?>

<div class="min-h-[calc(100vh-200px)] flex items-center justify-center px-4 py-12">
  <div class="w-full max-w-md">

<?php if (get('status') === 'pending'): ?>

    <!-- ── Pending verification state ───────────────────────────────────────── -->
    <div class="bg-brand-800 text-warm-50 rounded-2xl p-8 text-center shadow-xl">
      <div class="flex justify-center mb-5">
        <div class="bg-brand-400/20 rounded-full p-4">
          <i data-lucide="mail" class="w-10 h-10" style="color:#C6A75E"></i>
        </div>
      </div>
      <h1 class="text-2xl font-bold mb-3" style="font-family:'Playfair Display',Georgia,serif">
        Revisá tu email
      </h1>
      <?php $pe = $pendingEmail ?? ''; ?>
      <p class="text-warm-300 text-sm mb-6">
        Te enviamos un link de activación
        <?php if ($pe): ?> a <strong class="text-warm-100"><?= e($pe) ?></strong><?php endif; ?>.
        El link expira en 24 horas. Revisá también tu carpeta de spam.
      </p>
      <a href="/auth/resend-verification"
         class="inline-flex items-center gap-2 text-brand-400 text-sm hover:text-brand-300 underline underline-offset-2 transition">
        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
        ¿No recibiste el email? Solicitá uno nuevo
      </a>
      <div class="mt-6 border-t border-brand-700 pt-5">
        <a href="/auth/login" class="text-warm-400 text-xs hover:text-warm-200 transition">
          ← Volver al inicio de sesión
        </a>
      </div>
    </div>

<?php else: ?>

    <div class="text-center mb-8">
      <h1 class="text-3xl font-bold text-warm-800" style="font-family:'Playfair Display',Georgia,serif">Uníte a Tienda Moroni</h1>
      <p class="mt-2 text-warm-500 text-sm">¿Ya tenés cuenta?
        <a href="/auth/login" class="text-brand-800 font-semibold hover:text-brand-900 transition">Ingresá</a>
      </p>
    </div>

    <?php if (!empty($error)): ?>
    <div class="mb-5 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
      <?= e($error) ?>
    </div>
    <?php endif; ?>

    <!-- Google OAuth -->
    <a href="/auth/google"
       class="flex items-center justify-center gap-3 w-full border border-warm-300 rounded-xl py-3 text-sm font-medium text-warm-700 hover:bg-warm-50 transition mb-5">
      <svg class="w-5 h-5" viewBox="0 0 24 24">
        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
      </svg>
      Registrate con Google
    </a>

    <div class="flex items-center gap-3 mb-5">
      <hr class="flex-1 border-warm-200">
      <span class="text-xs text-warm-400">o con email</span>
      <hr class="flex-1 border-warm-200">
    </div>

    <form method="post" action="/auth/register" class="space-y-4">
      <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">

      <div>
        <label class="block text-sm font-medium text-warm-700 mb-1">Nombre completo</label>
        <input type="text" name="name" required autocomplete="name"
               class="w-full border border-warm-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
      </div>
      <div>
        <label class="block text-sm font-medium text-warm-700 mb-1">Email</label>
        <input type="email" name="email" required autocomplete="email"
               class="w-full border border-warm-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
      </div>
      <div>
        <label class="block text-sm font-medium text-warm-700 mb-1">Contraseña (mín. 8 caracteres)</label>
        <input type="password" name="password" required minlength="8"
               class="w-full border border-warm-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
      </div>
      <div>
        <label class="block text-sm font-medium text-warm-700 mb-1">Confirmá la contraseña</label>
        <input type="password" name="password_confirm" required minlength="8"
               class="w-full border border-warm-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
      </div>

      <button type="submit"
              class="w-full bg-brand-800 text-white py-3.5 rounded-xl font-bold text-sm hover:bg-brand-700 transition">
        Crear mi cuenta
      </button>

      <p class="text-xs text-warm-400 text-center">
        Al registrarte formás parte de nuestra comunidad de artesanos y compradores.
      </p>
    </form>

<?php endif; ?>

  </div>
</div>
