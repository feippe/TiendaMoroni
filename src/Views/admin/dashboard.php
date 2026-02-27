<?php $layout = 'layout/admin'; ?>

<!-- Stats cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
  <?php
  $cards = [
    ['label'=>'Pedidos',        'value'=> $stats['total_orders'],                                    'icon'=>'clipboard-list',  'bg'=>'#EFF6FF', 'color'=>'#2563EB'],
    ['label'=>'Pendientes',     'value'=> $stats['pending_orders'],                                  'icon'=>'clock',          'bg'=>'#FFFBEB', 'color'=>'#D97706'],
    ['label'=>'Ingresos (UYU)', 'value'=>'$ '.number_format($stats['total_revenue'],0,',','.'),      'icon'=>'circle-dollar-sign','bg'=>'#F0FDF4','color'=>'#16A34A'],
    ['label'=>'Productos',      'value'=> $stats['total_products'],                                  'icon'=>'package',        'bg'=>'#FAF5FF', 'color'=>'#9333EA'],
  ];
  ?>
  <?php foreach ($cards as $card): ?>
  <div class="bg-white rounded-2xl border border-warm-200 p-5">
    <div class="flex items-center justify-between mb-3">
      <span class="text-xs font-semibold text-warm-500 uppercase tracking-wider"><?= $card['label'] ?></span>
      <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:<?= $card['bg'] ?>">
        <i data-lucide="<?= $card['icon'] ?>" class="w-5 h-5" style="color:<?= $card['color'] ?>"></i>
      </div>
    </div>
    <p class="text-2xl font-bold text-warm-900"><?= $card['value'] ?></p>
  </div>
  <?php endforeach; ?>
</div>

<!-- Pending Q&A alert -->
<?php if ($pendingQA > 0): ?>
<div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-2xl px-5 py-3 flex items-center justify-between">
  <p class="text-sm text-yellow-800">
    Tenés <strong><?= $pendingQA ?></strong> pregunta<?= $pendingQA > 1 ? 's' : '' ?> sin responder.
  </p>
  <a href="/admin/preguntas" class="text-sm font-semibold text-yellow-700 hover:text-yellow-900 transition">
    Ir a preguntas →
  </a>
</div>
<?php endif; ?>

<!-- Recent orders -->
<div class="bg-white rounded-2xl border border-warm-200 overflow-hidden">
  <div class="flex items-center justify-between px-6 py-4 border-b border-warm-200">
    <h2 class="font-bold text-warm-900">Últimos pedidos</h2>
    <a href="/admin/pedidos" class="text-sm text-brand-800 hover:text-brand-900 transition font-medium">Ver todos →</a>
  </div>
  <?php if (empty($recentOrders)): ?>
  <div class="text-center py-10 text-warm-400 text-sm">Todavía no hay pedidos.</div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-warm-50 text-xs text-warm-500 uppercase tracking-wider">
        <tr>
          <th class="px-6 py-3 text-left">#</th>
          <th class="px-6 py-3 text-left">Comprador</th>
          <th class="px-6 py-3 text-left">Total</th>
          <th class="px-6 py-3 text-left">Estado</th>
          <th class="px-6 py-3 text-left">Fecha</th>
          <th class="px-6 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-warm-100">
        <?php foreach ($recentOrders as $order): ?>
        <?php
        $statusColors = [
          'pending'   => 'bg-yellow-100 text-yellow-700',
          'confirmed' => 'bg-blue-100 text-blue-700',
          'shipped'   => 'bg-indigo-100 text-indigo-700',
          'delivered' => 'bg-green-100 text-green-700',
          'cancelled' => 'bg-red-100 text-red-700',
        ];
        $statusLabels = [
          'pending'   => 'Pendiente',
          'confirmed' => 'Confirmada',
          'shipped'   => 'Enviada',
          'delivered' => 'Entregada',
          'cancelled' => 'Cancelada',
        ];
        ?>
        <tr class="hover:bg-warm-50 transition">
          <td class="px-6 py-3.5 font-mono font-semibold text-warm-900">#<?= (int)$order['id'] ?></td>
          <td class="px-6 py-3.5 text-warm-700"><?= e($order['buyer_name']) ?></td>
          <td class="px-6 py-3.5 font-semibold"><?= formatPrice($order['total']) ?></td>
          <td class="px-6 py-3.5">
            <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $statusColors[$order['status']] ?? 'bg-warm-100 text-warm-600' ?>">
              <?= $statusLabels[$order['status']] ?? $order['status'] ?>
            </span>
          </td>
          <td class="px-6 py-3.5 text-warm-500"><?= date('d/m/Y', strtotime($order['created_at'])) ?></td>
          <td class="px-6 py-3.5">
            <a href="/admin/ordenes/<?= (int)$order['id'] ?>"
               class="text-brand-800 hover:text-brand-900 font-medium transition text-xs">Ver →</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
