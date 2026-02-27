<?php $layout = 'layout/admin'; ?>

<div class="mb-6 flex justify-between items-center">
  <h2 class="text-xl font-bold text-warm-900">Categorías</h2>
  <a href="/admin/categorias/nueva"
     class="bg-brand-800 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-brand-700 transition">
    + Nueva categoría
  </a>
</div>

<?php if (!empty($flash)): ?>
<div class="mb-4 bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm"><?= e($flash) ?></div>
<?php endif; ?>

<div class="bg-white rounded-2xl border border-warm-200 overflow-hidden">
  <table class="min-w-full text-sm">
    <thead class="bg-warm-50 text-warm-600 uppercase text-xs tracking-wider">
      <tr>
        <th class="px-5 py-3 text-left">Nombre</th>
        <th class="px-5 py-3 text-left">Slug</th>
        <th class="px-5 py-3 text-left">Padre</th>
        <th class="px-5 py-3 text-left">Orden</th>
        <th class="px-5 py-3 text-left">Acciones</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-warm-100">
      <?php if (empty($categories)): ?>
      <tr>
        <td colspan="5" class="px-5 py-8 text-center text-warm-400">No hay categorías aún.</td>
      </tr>
      <?php else: ?>
      <?php foreach ($categories as $cat): ?>
      <tr class="hover:bg-warm-50 transition">
        <td class="px-5 py-3 font-medium text-warm-900">
          <?= e($cat['name']) ?>
        </td>
        <td class="px-5 py-3 font-mono text-warm-500 text-xs"><?= e($cat['slug']) ?></td>
        <td class="px-5 py-3 text-warm-500"><?= e($cat['parent_name'] ?? '—') ?></td>
        <td class="px-5 py-3 text-warm-500"><?= (int)$cat['sort_order'] ?></td>
        <td class="px-5 py-3">
          <div class="flex items-center gap-3">
            <a href="/admin/categorias/<?= (int)$cat['id'] ?>/editar"
               class="text-brand-600 hover:text-brand-800 font-medium transition">Editar</a>
            <form method="post" action="/admin/categorias/<?= (int)$cat['id'] ?>/eliminar"
                  onsubmit="return confirm('¿Eliminar categoría «<?= e(addslashes($cat['name'])) ?>»?')">
              <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
              <button type="submit" class="text-red-500 hover:text-red-700 font-medium transition">Eliminar</button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
