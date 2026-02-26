<?php $layout = 'layout/admin'; ?>

<div class="mb-6">
  <h2 class="text-xl font-bold text-warm-900">Vendedores</h2>
</div>

<div class="bg-white rounded-2xl border border-warm-200 overflow-hidden">
  <table class="min-w-full text-sm">
    <thead class="bg-warm-50 text-warm-600 uppercase text-xs tracking-wider">
      <tr>
        <th class="px-5 py-3 text-left">Negocio</th>
        <th class="px-5 py-3 text-left">Usuario</th>
        <th class="px-5 py-3 text-left">Estado</th>
        <th class="px-5 py-3 text-left">Registrado</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-warm-100">
      <?php if (empty($vendors)): ?>
      <tr>
        <td colspan="4" class="px-5 py-8 text-center text-warm-400">No hay vendedores registrados.</td>
      </tr>
      <?php else: ?>
      <?php foreach ($vendors as $v): ?>
      <tr class="hover:bg-warm-50 transition">
        <td class="px-5 py-3">
          <div class="font-medium text-warm-900"><?= e($v['business_name']) ?></div>
          <?php if (!empty($v['business_description'])): ?>
          <div class="text-xs text-warm-400"><?= truncate(e($v['business_description']), 80) ?></div>
          <?php endif; ?>
        </td>
        <td class="px-5 py-3 text-warm-600"><?= e($v['user_email'] ?? '—') ?></td>
        <td class="px-5 py-3">
          <?php if (!empty($v['is_verified'])): ?>
          <span class="px-2 py-0.5 bg-green-100 text-green-800 rounded-full text-xs font-semibold">Verificado</span>
          <?php else: ?>
          <span class="px-2 py-0.5 bg-warm-100 text-warm-700 rounded-full text-xs font-semibold">Pendiente</span>
          <?php endif; ?>
        </td>
        <td class="px-5 py-3 text-warm-500"><?= date('d/m/Y', strtotime($v['created_at'])) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
