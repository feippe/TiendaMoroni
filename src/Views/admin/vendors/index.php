<?php $layout = 'layout/admin'; ?>

<div class="mb-6 flex items-center justify-between">
  <h2 class="text-xl font-bold text-warm-900">Vendedores</h2>
  <a href="/admin/vendedores/nuevo"
     class="flex items-center gap-2 bg-brand-800 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-brand-700 transition">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
    </svg>
    Nuevo vendedor
  </a>
</div>

<div class="bg-white rounded-2xl border border-warm-200 overflow-hidden">
  <table class="min-w-full text-sm">
    <thead class="bg-warm-50 text-warm-600 uppercase text-xs tracking-wider">
      <tr>
        <th class="px-5 py-3 text-left">Negocio</th>
        <th class="px-5 py-3 text-left">Email</th>
        <th class="px-5 py-3 text-left">Teléfono</th>
        <th class="px-5 py-3 text-left">Estado</th>
        <th class="px-5 py-3 text-left">Registrado</th>
        <th class="px-5 py-3 text-right">Acciones</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-warm-100">
      <?php if (empty($vendors)): ?>
      <tr>
        <td colspan="6" class="px-5 py-8 text-center text-warm-400">No hay vendedores registrados.</td>
      </tr>
      <?php else: ?>
      <?php foreach ($vendors as $v): ?>
      <tr class="hover:bg-warm-50 transition">
        <td class="px-5 py-3">
          <div class="font-medium text-warm-900"><?= e($v['business_name']) ?></div>
          <?php if (!empty($v['business_description'])): ?>
          <div class="text-xs text-warm-400"><?= e(mb_substr($v['business_description'], 0, 80)) ?><?= mb_strlen($v['business_description']) > 80 ? '…' : '' ?></div>
          <?php endif; ?>
        </td>
        <td class="px-5 py-3 text-warm-600"><?= e($v['user_email'] ?? $v['email'] ?? '—') ?></td>
        <td class="px-5 py-3 text-warm-600"><?= e($v['phone'] ?? '—') ?></td>
        <td class="px-5 py-3">
          <?php if (!empty($v['is_verified'])): ?>
          <span class="px-2 py-0.5 bg-green-100 text-green-800 rounded-full text-xs font-semibold">Verificado</span>
          <?php else: ?>
          <span class="px-2 py-0.5 bg-warm-100 text-warm-700 rounded-full text-xs font-semibold">Pendiente</span>
          <?php endif; ?>
        </td>
        <td class="px-5 py-3 text-warm-500"><?= date('d/m/Y', strtotime($v['created_at'])) ?></td>
        <td class="px-5 py-3 text-right">
          <div class="flex items-center justify-end gap-2">
            <a href="/admin/vendedores/<?= (int)$v['id'] ?>/editar"
               class="text-xs px-3 py-1.5 border border-warm-200 rounded-lg text-warm-700 hover:border-brand-400 hover:text-brand-800 transition">
              Editar
            </a>
            <form method="post" action="/admin/vendedores/<?= (int)$v['id'] ?>/eliminar"
                  onsubmit="return confirm('¿Eliminar este vendedor? También se eliminarán sus productos.')">
              <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
              <button type="submit"
                      class="text-xs px-3 py-1.5 border border-red-200 rounded-lg text-red-600 hover:bg-red-50 transition">
                Eliminar
              </button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
