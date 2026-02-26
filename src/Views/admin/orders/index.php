<?php $layout = 'layout/admin'; ?>

<div class="mb-6 flex justify-between items-center">
  <h2 class="text-xl font-bold text-warm-900">Pedidos</h2>
</div>

<!-- Status filter tabs -->
<?php
$statuses = ['' => 'Todos', 'pending' => 'Pendientes', 'confirmed' => 'Confirmados',
             'shipped' => 'Enviados', 'delivered' => 'Entregados', 'cancelled' => 'Cancelados'];
$current = $status ?? '';
?>
<div class="flex gap-2 mb-5 flex-wrap">
  <?php foreach ($statuses as $val => $label): ?>
  <a href="/admin/pedidos<?= $val ? '?status=' . $val : '' ?>"
     class="px-4 py-1.5 rounded-full text-sm font-medium transition
            <?= $current === $val ? 'bg-brand-700 text-white' : 'bg-white border border-warm-200 text-warm-600 hover:border-brand-400' ?>">
    <?= $label ?>
  </a>
  <?php endforeach; ?>
</div>

<div class="bg-white rounded-2xl border border-warm-200 overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-warm-50 text-warm-600 uppercase text-xs tracking-wider">
      <tr>
        <th class="px-5 py-3 text-left">#</th>
        <th class="px-5 py-3 text-left">Cliente</th>
        <th class="px-5 py-3 text-left">Fecha</th>
        <th class="px-5 py-3 text-left">Total</th>
        <th class="px-5 py-3 text-left">Estado</th>
        <th class="px-5 py-3 text-left">Acción</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-warm-100">
      <?php if (empty($orders)): ?>
      <tr>
        <td colspan="6" class="px-5 py-8 text-center text-warm-400">No hay pedidos<?= $current ? ' con este estado' : '' ?>.</td>
      </tr>
      <?php else: ?>
      <?php
      $statusColors = [
        'pending'   => 'bg-yellow-100 text-yellow-800',
        'confirmed' => 'bg-blue-100 text-blue-800',
        'shipped'   => 'bg-indigo-100 text-indigo-800',
        'delivered' => 'bg-green-100 text-green-800',
        'cancelled' => 'bg-red-100 text-red-800',
      ];
      $statusLabels = [
        'pending'   => 'Pendiente',
        'confirmed' => 'Confirmado',
        'shipped'   => 'Enviado',
        'delivered' => 'Entregado',
        'cancelled' => 'Cancelado',
      ];
      ?>
      <?php foreach ($orders as $o): ?>
      <tr class="hover:bg-warm-50 transition">
        <td class="px-5 py-3 font-mono text-warm-500">#<?= (int)$o['id'] ?></td>
        <td class="px-5 py-3">
          <div class="font-medium text-warm-900"><?= e($o['buyer_name']) ?></div>
          <div class="text-xs text-warm-400"><?= e($o['buyer_email']) ?></div>
        </td>
        <td class="px-5 py-3 text-warm-500 whitespace-nowrap">
          <?= date('d/m/Y H:i', strtotime($o['created_at'])) ?>
        </td>
        <td class="px-5 py-3 font-semibold text-warm-900"><?= formatPrice($o['total']) ?></td>
        <td class="px-5 py-3">
          <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $statusColors[$o['status']] ?? 'bg-warm-100 text-warm-700' ?>">
            <?= $statusLabels[$o['status']] ?? ucfirst($o['status']) ?>
          </span>
        </td>
        <td class="px-5 py-3">
          <a href="/admin/pedidos/<?= (int)$o['id'] ?>"
             class="text-brand-600 hover:text-brand-800 font-medium transition">Ver</a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php if (!empty($pagination)): ?>
<div class="mt-4"><?php partial('pagination', $pagination) ?></div>
<?php endif; ?>
