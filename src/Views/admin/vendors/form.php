<?php $layout = 'layout/admin'; ?>

<div class="mb-6 flex items-center gap-3">
  <a href="/admin/vendedores" class="text-warm-500 hover:text-warm-900 transition text-sm">← Volver</a>
  <h2 class="text-xl font-bold text-warm-900">
    <?= $vendor ? 'Editar: ' . e($vendor['business_name']) : 'Nuevo vendedor' ?>
  </h2>
</div>

<?php if (!empty($error)): ?>
<div class="mb-5 bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 text-sm">
  <?= e($error) ?>
</div>
<?php endif; ?>

<form method="post"
      action="<?= $vendor ? '/admin/vendedores/' . (int)$vendor['id'] . '/actualizar' : '/admin/vendedores/guardar' ?>"
      class="max-w-2xl space-y-5">

  <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">

  <div class="bg-white rounded-2xl border border-warm-200 p-6 space-y-4">

    <div>
      <label class="block text-sm font-medium text-warm-700 mb-1">Nombre del negocio *</label>
      <input type="text" name="business_name" required
             value="<?= e($vendor['business_name'] ?? '') ?>"
             class="w-full border border-warm-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
    </div>

    <div>
      <label class="block text-sm font-medium text-warm-700 mb-1">Descripción del negocio</label>
      <textarea name="business_description" rows="3"
                class="w-full border border-warm-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition resize-none"
      ><?= e($vendor['business_description'] ?? '') ?></textarea>
    </div>

    <div>
      <label class="block text-sm font-medium text-warm-700 mb-1">Email *</label>
      <?php if ($vendor): ?>
      <input type="email" name="email" required
             value="<?= e($vendor['user_email'] ?? $vendor['email'] ?? '') ?>"
             class="w-full border border-warm-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
      <p class="text-xs text-warm-400 mt-1">El email del usuario asociado no se modifica al editar.</p>
      <?php else: ?>
      <input type="email" name="email" required
             value=""
             class="w-full border border-warm-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
      <p class="text-xs text-warm-400 mt-1">Si el email no existe en el sistema, se creará un usuario automáticamente.</p>
      <?php endif; ?>
    </div>

    <div>
      <label class="block text-sm font-medium text-warm-700 mb-1">Teléfono</label>
      <input type="text" name="phone"
             value="<?= e($vendor['phone'] ?? '') ?>"
             class="w-full border border-warm-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
    </div>

    <div class="border-t border-warm-100 pt-4">
      <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="is_verified" value="1"
               <?= !empty($vendor['is_verified']) ? 'checked' : '' ?>
               class="rounded text-brand-600">
        <span class="text-sm text-warm-700">Vendedor verificado ✓</span>
      </label>
    </div>

  </div>

  <div class="flex gap-3">
    <button type="submit"
            class="bg-brand-800 text-white px-6 py-2.5 rounded-xl text-sm font-bold hover:bg-brand-700 transition">
      <?= $vendor ? 'Guardar cambios' : 'Crear vendedor' ?>
    </button>
    <a href="/admin/vendedores"
       class="px-6 py-2.5 border border-warm-300 rounded-xl text-sm text-warm-700 hover:border-warm-500 transition">
      Cancelar
    </a>
  </div>

</form>
