<?php $layout = 'layout/admin'; ?>

<div class="mb-6 flex items-center gap-3">
  <a href="/admin/categorias" class="text-warm-500 hover:text-warm-900 transition text-sm">← Volver</a>
  <h2 class="text-xl font-bold text-warm-900">
    <?= $category ? 'Editar: ' . e($category['name']) : 'Nueva categoría' ?>
  </h2>
</div>

<?php if (!empty($saved)): ?>
<div class="mb-5 bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">
  ✓ Categoría guardada correctamente.
</div>
<?php endif; ?>

<form method="post"
      action="<?= $category ? '/admin/categorias/' . (int)$category['id'] . '/actualizar' : '/admin/categorias/guardar' ?>"
      class="max-w-2xl space-y-5"
      x-data="{ slug: '<?= e($category['slug'] ?? '') ?>', imageUrl: '<?= e($category['image_url'] ?? '') ?>', updateSlug(v){ this.slug = v.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9\s-]/g,'').replace(/[\s-]+/g,'-').trim() } }">

  <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">

  <div class="bg-white rounded-2xl border border-warm-200 p-6 space-y-4">

    <div>
      <label class="block text-sm font-medium text-warm-700 mb-1">Nombre *</label>
      <input type="text" name="name" required
             value="<?= e($category['name'] ?? '') ?>"
             @input="updateSlug($event.target.value)"
             class="w-full border border-warm-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
    </div>

    <div>
      <label class="block text-sm font-medium text-warm-700 mb-1">Slug</label>
      <input type="text" name="slug" x-model="slug"
             class="w-full border border-warm-300 rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
    </div>

    <div>
      <label class="block text-sm font-medium text-warm-700 mb-1">Categoría padre</label>
      <select name="parent_id"
              class="w-full border border-warm-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
        <option value="">Sin padre (categoría raíz)</option>
        <?php foreach ($allCategories as $c): ?>
        <?php if ($category && (int)$c['id'] === (int)$category['id']) continue; ?>
        <option value="<?= (int)$c['id'] ?>"
                <?= (int)($category['parent_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>>
          <?= e($c['name']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium text-warm-700 mb-1">Descripción</label>
      <textarea name="description" rows="3"
                class="w-full border border-warm-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition resize-none"
      ><?= e($category['description'] ?? '') ?></textarea>
    </div>

    <div>
      <label class="block text-sm font-medium text-warm-700 mb-1">Imagen (opcional)</label>
      <input type="hidden" name="image_url" x-model="imageUrl">

      <div x-show="imageUrl" class="mb-3 relative inline-block">
        <img :src="imageUrl" alt="" class="h-28 w-28 rounded-xl object-cover border border-warm-200">
        <button type="button" @click="imageUrl = ''"
                class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full text-xs flex items-center justify-center hover:bg-red-600 transition">
          ×
        </button>
      </div>

      <button type="button"
              @click="window.dispatchEvent(new CustomEvent('open-media-picker', { detail: { callback: (f) => imageUrl = f.url } }))"
              class="flex items-center gap-2 px-4 py-2 border border-warm-300 rounded-xl text-sm text-warm-700 hover:border-brand-400 hover:text-brand-700 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3 9h18M3 6h18"/>
        </svg>
        <span x-text="imageUrl ? 'Cambiar imagen' : 'Elegir del repositorio'"></span>
      </button>
    </div>

    <div>
      <label class="block text-sm font-medium text-warm-700 mb-1">Orden de visualización</label>
      <input type="number" name="sort_order" min="0"
             value="<?= (int)($category['sort_order'] ?? 0) ?>"
             class="w-40 border border-warm-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
    </div>

    <div class="border-t border-warm-100 pt-4 space-y-3">
      <h4 class="text-sm font-semibold text-warm-800">SEO</h4>
      <div>
        <label class="block text-sm font-medium text-warm-700 mb-1">Meta título</label>
        <input type="text" name="meta_title" maxlength="160"
               value="<?= e($category['meta_title'] ?? '') ?>"
               class="w-full border border-warm-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
      </div>
      <div>
        <label class="block text-sm font-medium text-warm-700 mb-1">Meta descripción</label>
        <textarea name="meta_description" rows="2" maxlength="320"
                  class="w-full border border-warm-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition resize-none"
        ><?= e($category['meta_description'] ?? '') ?></textarea>
      </div>
    </div>

    <button type="submit"
            class="w-full bg-brand-700 text-white py-2.5 rounded-xl text-sm font-bold hover:bg-brand-800 transition">
      <?= $category ? 'Guardar cambios' : 'Crear categoría' ?>
    </button>
  </div>

</form>
