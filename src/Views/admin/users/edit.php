<?php $layout = 'layout/admin'; ?>
<?php
$cu       = $currentUser ?? [];
$isSelf   = (int)($cu['id'] ?? 0) === (int)$user['id'];
$old      = $old ?? [];
$errors   = $errors ?? [];
$val      = fn(string $k) => e($old[$k] ?? $user[$k] ?? '');
?>

<!-- Breadcrumb -->
<div class="flex items-center gap-2 text-sm text-warm-400 mb-6">
  <a href="/admin/usuarios" class="hover:text-warm-700 transition">Usuarios</a>
  <span>/</span>
  <a href="/admin/usuarios/<?= $user['id'] ?>" class="hover:text-warm-700 transition"><?= e($user['name']) ?></a>
  <span>/</span>
  <span class="text-warm-700">Editar</span>
</div>

<div class="max-w-lg">
  <div class="flex items-center gap-3 mb-6">
    <i data-lucide="pencil" class="w-5 h-5 text-brand-400"></i>
    <h1 class="text-xl font-semibold text-warm-900">Editar usuario</h1>
  </div>

  <div class="bg-white rounded-2xl border border-warm-200 p-6">
    <form method="POST" action="/admin/usuarios/<?= $user['id'] ?>/editar" class="space-y-5">

      <!-- Nombre -->
      <div>
        <label for="name" class="block text-sm font-medium text-warm-800 mb-1">
          Nombre <span class="text-red-500">*</span>
        </label>
        <input type="text" id="name" name="name"
               value="<?= $val('name') ?>"
               required
               class="w-full px-3 py-2.5 text-sm border rounded-xl bg-white focus:outline-none focus:ring-2
                      <?= isset($errors['name']) ? 'border-red-400 focus:ring-red-200' : 'border-warm-200 focus:ring-gold-tint' ?>">
        <?php if (isset($errors['name'])): ?>
        <p class="text-xs text-red-500 mt-1"><?= e($errors['name']) ?></p>
        <?php endif; ?>
      </div>

      <!-- Rol -->
      <div>
        <label for="role" class="block text-sm font-medium text-warm-800 mb-1">Rol</label>
        <?php if ($isSelf): ?>
        <input type="text" value="<?= e($user['role'] === 'admin' ? 'Admin' : 'Comprador') ?>"
               disabled class="w-full px-3 py-2.5 text-sm border border-warm-200 rounded-xl bg-warm-50 text-warm-500 cursor-not-allowed">
        <input type="hidden" name="role" value="<?= e($user['role']) ?>">
        <p class="text-xs text-warm-400 mt-1">No podés cambiar tu propio rol.</p>
        <?php else: ?>
        <select id="role" name="role"
                class="w-full px-3 py-2.5 text-sm border rounded-xl bg-white focus:outline-none focus:ring-2
                       <?= isset($errors['role']) ? 'border-red-400 focus:ring-red-200' : 'border-warm-200 focus:ring-gold-tint' ?>">
          <option value="buyer" <?= ($old['role'] ?? $user['role']) === 'buyer' ? 'selected' : '' ?>>Comprador</option>
          <option value="admin" <?= ($old['role'] ?? $user['role']) === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>
        <?php if (isset($errors['role'])): ?>
        <p class="text-xs text-red-500 mt-1"><?= e($errors['role']) ?></p>
        <?php endif; ?>
        <?php endif; ?>
      </div>

      <!-- Read-only: Email -->
      <div>
        <label class="block text-sm font-medium text-warm-800 mb-1">Email</label>
        <input type="text" value="<?= e($user['email']) ?>" disabled
               class="w-full px-3 py-2.5 text-sm border border-warm-200 rounded-xl bg-warm-50 text-warm-500 cursor-not-allowed">
        <p class="text-xs text-warm-400 mt-1">El email no puede modificarse.</p>
      </div>

      <!-- Read-only: Auth provider -->
      <div>
        <label class="block text-sm font-medium text-warm-800 mb-1">Proveedor de autenticación</label>
        <input type="text" value="<?= e($user['auth_provider'] === 'google' ? 'Google' : 'Propio (contraseña)') ?>" disabled
               class="w-full px-3 py-2.5 text-sm border border-warm-200 rounded-xl bg-warm-50 text-warm-500 cursor-not-allowed">
      </div>

      <!-- Read-only: Registration date -->
      <div>
        <label class="block text-sm font-medium text-warm-800 mb-1">Fecha de registro</label>
        <input type="text" value="<?= date('d/m/Y H:i', strtotime($user['created_at'])) ?>" disabled
               class="w-full px-3 py-2.5 text-sm border border-warm-200 rounded-xl bg-warm-50 text-warm-500 cursor-not-allowed">
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-between pt-2">
        <a href="/admin/usuarios/<?= $user['id'] ?>"
           class="text-sm text-warm-400 hover:text-warm-700 transition">
          ← Cancelar
        </a>
        <button type="submit"
                class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-medium text-white transition"
                style="background:var(--color-navy-deeper)"
                onmouseover="this.style.background='#152B54'"
                onmouseout="this.style.background='#0D1C38'">
          <i data-lucide="save" class="w-4 h-4"></i>
          Guardar cambios
        </button>
      </div>

    </form>
  </div>
</div>
