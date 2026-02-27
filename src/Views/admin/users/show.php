<?php $layout = 'layout/admin'; ?>
<?php
$cu         = $currentUser ?? [];
$isSelf     = (int)($cu['id'] ?? 0) === (int)$user['id'];
$initials   = mb_strtoupper(mb_substr($user['name'], 0, 1))
            . (str_contains($user['name'], ' ') ? mb_strtoupper(mb_substr(strstr($user['name'], ' '), 1, 1)) : '');
$roleLabels = ['admin' => 'Admin', 'buyer' => 'Comprador'];
$statusLabels = ['pending'=>'Pendiente','confirmed'=>'Confirmado',
                 'shipped'=>'Enviado','delivered'=>'Entregado','cancelled'=>'Cancelado'];
$statusColors = ['pending'=>'bg-yellow-100 text-yellow-800','confirmed'=>'bg-blue-100 text-blue-800',
                 'shipped'=>'bg-indigo-100 text-indigo-800','delivered'=>'bg-green-100 text-green-800',
                 'cancelled'=>'bg-red-100 text-red-800'];
?>

<!-- Flash messages -->
<?php if ($flash ?? null): ?>
<div class="mb-4 bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">
  <?= e($flash) ?>
</div>
<?php endif; ?>
<?php if ($error ?? null): ?>
<div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
  <?= e($error) ?>
</div>
<?php endif; ?>

<!-- Breadcrumb -->
<div class="flex items-center gap-2 text-sm text-warm-400 mb-6">
  <a href="/admin/usuarios" class="hover:text-warm-700 transition">Usuarios</a>
  <span>/</span>
  <span class="text-warm-700"><?= e($user['name']) ?></span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  <!-- Left: Profile card -->
  <div class="lg:col-span-1">
    <div class="bg-white rounded-2xl border border-warm-200 p-6 text-center">
      <!-- Avatar -->
      <?php if ($user['avatar_url']): ?>
      <img src="<?= e($user['avatar_url']) ?>" alt="<?= e($user['name']) ?>"
           class="w-16 h-16 rounded-full object-cover mx-auto mb-4">
      <?php else: ?>
      <div class="w-16 h-16 rounded-full flex items-center justify-center text-lg font-bold text-white mx-auto mb-4"
           style="background:#162E4A"><?= e($initials) ?></div>
      <?php endif; ?>

      <h2 class="text-xl font-serif font-bold text-warm-900 mb-1"><?= e($user['name']) ?></h2>
      <p class="text-sm text-warm-400 mb-4"><?= e($user['email']) ?></p>

      <!-- Badges -->
      <div class="flex flex-wrap gap-2 justify-center mb-5">
        <span class="text-xs font-medium px-2.5 py-1 rounded-full"
              style="<?= $user['role'] === 'admin' ? 'background:#0F1E2E;color:#F8F6F2' : 'background:#f0ede6;color:#5a5450' ?>">
          <?= e($roleLabels[$user['role']] ?? $user['role']) ?>
        </span>
        <span class="text-xs px-2.5 py-1 rounded-full flex items-center gap-1
                     <?= $user['active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' ?>">
          <?= $user['active'] ? 'Activo' : 'Inactivo' ?>
        </span>
        <span class="text-xs px-2.5 py-1 rounded-full bg-warm-100 text-warm-500 flex items-center gap-1">
          <?php if ($user['auth_provider'] === 'google'): ?>
          <i data-lucide="chrome" class="w-3 h-3"></i> Google
          <?php else: ?>
          <i data-lucide="mail" class="w-3 h-3"></i> Propio
          <?php endif; ?>
        </span>
      </div>

      <p class="text-xs text-warm-400 mb-5">
        Registrado el <?= date('d/m/Y', strtotime($user['created_at'])) ?>
      </p>

      <?php if ($isSelf): ?>
      <p class="text-xs text-warm-400 italic bg-warm-50 rounded-xl px-3 py-2">
        Este es tu propio usuario
      </p>
      <?php else: ?>
      <!-- Actions -->
      <div class="flex flex-col gap-2"
           x-data="{ active: <?= $user['active'] ? 'true' : 'false' ?>, busy: false }">
        <a href="/admin/usuarios/<?= $user['id'] ?>/editar"
           class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium border border-warm-200 hover:border-brand-400 transition text-warm-700">
          <i data-lucide="pencil" class="w-4 h-4"></i> Editar usuario
        </a>
        <button type="button"
                :disabled="busy"
                class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium border transition"
                :class="active
                  ? 'border-red-200 text-red-600 hover:bg-red-50'
                  : 'border-green-200 text-green-600 hover:bg-green-50'"
                @click="
                  busy=true;
                  fetch('/admin/usuarios/<?= $user['id'] ?>/toggle-status',{method:'POST',
                    headers:{'Content-Type':'application/json','X-CSRF-Token':document.querySelector('meta[name=csrf-token]')?.content??''}
                  })
                  .then(r=>r.json())
                  .then(d=>{ if(d.success){ active=d.active===1; } })
                  .catch(()=>{})
                  .finally(()=>{ busy=false; })
                ">
          <i :data-lucide="active ? 'user-x' : 'user-check'" class="w-4 h-4"></i>
          <span x-text="active ? 'Desactivar' : 'Activar'"></span>
        </button>
        <!-- Delete -->
        <div x-data="{
               modalOpen: false,
               submit() { this.$refs.df.submit(); }
             }">
          <button type="button"
                  @click="modalOpen = true"
                  class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm text-red-500 hover:bg-red-50 border border-transparent hover:border-red-200 transition">
            <i data-lucide="trash-2" class="w-4 h-4"></i> Eliminar
          </button>
          <!-- Modal -->
          <div x-show="modalOpen"
               x-transition:enter="transition ease-out duration-200"
               x-transition:enter-start="opacity-0"
               x-transition:enter-end="opacity-100"
               x-transition:leave="transition ease-in duration-150"
               x-transition:leave-start="opacity-100"
               x-transition:leave-end="opacity-0"
               @keydown.escape.window="modalOpen=false"
               role="dialog" aria-modal="true" aria-labelledby="del-title-show"
               class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
               x-cloak>
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6" @click.stop>
              <h3 id="del-title-show" class="text-lg font-semibold text-warm-900 mb-2">Confirmar eliminación</h3>
              <p class="text-sm text-warm-500 mb-6">
                ¿Confirmás que querés eliminar a <strong><?= e($user['name']) ?></strong>?
                Esta acción no se puede deshacer.
              </p>
              <div class="flex justify-end gap-3">
                <button type="button" @click="modalOpen=false"
                        class="px-4 py-2 rounded-xl text-sm border border-warm-200 text-warm-700 hover:bg-warm-50 transition">Cancelar</button>
                <form method="POST" action="/admin/usuarios/<?= $user['id'] ?>/eliminar" x-ref="df">
                  <input type="hidden" name="confirm_id" value="<?= $user['id'] ?>">
                  <button type="submit" class="px-4 py-2 rounded-xl text-sm font-medium bg-red-600 text-white hover:bg-red-700 transition">Eliminar</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Right: Orders -->
  <div class="lg:col-span-2">
    <div class="bg-white rounded-2xl border border-warm-200 p-6">
      <div class="flex items-center justify-between mb-5">
        <div class="flex items-center gap-2">
          <i data-lucide="shopping-bag" class="w-5 h-5 text-brand-400"></i>
          <h3 class="font-semibold text-warm-900">Últimas órdenes</h3>
        </div>
        <a href="/admin/pedidos?user_id=<?= $user['id'] ?>"
           class="text-xs text-brand-400 hover:underline">Ver todas →</a>
      </div>

      <?php if (empty($orders)): ?>
      <p class="text-sm text-warm-400 text-center py-8">Este usuario aún no tiene órdenes.</p>
      <?php else: ?>
      <table class="min-w-full text-sm" aria-label="Órdenes del usuario">
        <thead class="text-xs text-warm-500 uppercase tracking-wider bg-warm-50">
          <tr>
            <th scope="col" class="px-4 py-2.5 text-left">#</th>
            <th scope="col" class="px-4 py-2.5 text-left">Fecha</th>
            <th scope="col" class="px-4 py-2.5 text-left">Total</th>
            <th scope="col" class="px-4 py-2.5 text-left">Estado</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-warm-100">
          <?php foreach ($orders as $ord): ?>
          <tr class="hover:bg-warm-50/50 transition">
            <td class="px-4 py-3">
              <a href="/admin/pedidos/<?= $ord['id'] ?>" class="font-medium text-brand-800 hover:underline">#<?= $ord['id'] ?></a>
            </td>
            <td class="px-4 py-3 text-warm-500"><?= date('d/m/Y', strtotime($ord['created_at'])) ?></td>
            <td class="px-4 py-3 font-medium">$ <?= number_format((float)$ord['total'], 0, ',', '.') ?></td>
            <td class="px-4 py-3">
              <span class="text-xs px-2 py-1 rounded-full <?= $statusColors[$ord['status']] ?? 'bg-warm-100 text-warm-600' ?>">
                <?= e($statusLabels[$ord['status']] ?? $ord['status']) ?>
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>
