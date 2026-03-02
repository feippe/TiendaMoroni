<?php $layout = 'layout/admin'; ?>
<?php
$cu         = $currentUser ?? [];
$hasFilters = !empty($filters['q']) || !empty($filters['role']);

$roleLabels = ['admin' => 'Admin', 'buyer' => 'Comprador'];
$roleColors = [
    'admin' => 'background:var(--color-navy-deeper);color:var(--color-white)',
    'buyer' => 'background:#f0ede6;color:#5a5450',
];
$statusColors = ['pending'=>'bg-yellow-100 text-yellow-800','confirmed'=>'bg-blue-100 text-blue-800',
                 'shipped'=>'bg-indigo-100 text-indigo-800','delivered'=>'bg-green-100 text-green-800',
                 'cancelled'=>'bg-red-100 text-red-800'];

/** Build a query-string preserving current filters. */
function usersUrl(array $merge = []): string {
    global $filters, $pager;
    $base = array_filter(array_merge(['q' => $filters['q'], 'role' => $filters['role']], $merge));
    return '/admin/usuarios' . ($base ? '?' . http_build_query($base) : '');
}
?>

<!-- Delete confirm modal (shared, Alpine-driven) -->
<div x-data="{
       open: false,
       userId: 0,
       userName: '',
       open(id, name) { this.userId = id; this.userName = name; this.open = true; },
       close() { this.open = false; }
     }"
     @keydown.escape.window="close()"
     id="delete-modal-root">

  <!-- Page header -->
  <div class="flex items-center gap-3 mb-6">
    <i data-lucide="users" class="w-6 h-6 text-brand-400"></i>
    <h1 class="text-xl font-semibold text-warm-900">Usuarios</h1>
  </div>

  <!-- Stats row -->
  <div class="grid grid-cols-3 gap-4 mb-6">
    <?php foreach ([
      ['label' => 'Total', 'value' => $totalAll],
      ['label' => 'Activos', 'value' => $totalActive],
      ['label' => 'Inactivos', 'value' => $totalInactive],
    ] as $card): ?>
    <div class="bg-white rounded-xl border border-warm-200 px-5 py-4 shadow-sm">
      <p class="text-2xl font-bold text-brand-800"><?= $card['value'] ?></p>
      <p class="text-xs text-warm-400 mt-1"><?= $card['label'] ?></p>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Filters bar -->
  <form method="GET" action="/admin/usuarios" id="filter-form"
        class="flex flex-wrap gap-3 items-center mb-5"
        x-data="{
          debounceTimer: null,
          debounce(fn, ms) { clearTimeout(this.debounceTimer); this.debounceTimer = setTimeout(fn, ms); }
        }">
    <!-- Search -->
    <div class="relative flex-1 min-w-48">
      <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-warm-400">
        <i data-lucide="search" class="w-4 h-4"></i>
      </span>
      <input type="text" name="q" value="<?= e($filters['q']) ?>"
             placeholder="Buscar por nombre o email..."
             class="w-full pl-9 pr-3 py-2 text-sm border border-warm-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-brand-400"
             @input="debounce(() => $el.form.submit(), 400)">
    </div>
    <!-- Role filter -->
    <select name="role"
            class="text-sm border border-warm-200 rounded-xl px-3 py-2 bg-white text-warm-700 focus:outline-none"
            @change="$el.form.submit()">
      <option value="" <?= $filters['role'] === '' ? 'selected' : '' ?>>Todos los roles</option>
      <option value="buyer" <?= $filters['role'] === 'buyer' ? 'selected' : '' ?>>Comprador</option>
      <option value="admin" <?= $filters['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
    </select>
    <?php if ($hasFilters): ?>
    <a href="/admin/usuarios" class="text-sm text-warm-400 hover:text-warm-700 transition underline">
      Limpiar filtros
    </a>
    <?php endif; ?>
  </form>

  <!-- Table card -->
  <div class="bg-white rounded-2xl border border-warm-200 overflow-x-auto">
    <table class="min-w-full text-sm" aria-label="Lista de usuarios">
      <thead class="bg-warm-50 text-warm-500 text-xs uppercase tracking-wider">
        <tr>
          <th scope="col" class="px-5 py-3 text-left">Usuario</th>
          <th scope="col" class="px-5 py-3 text-left">Rol</th>
          <th scope="col" class="px-5 py-3 text-left">Proveedor</th>
          <th scope="col" class="px-5 py-3 text-left">Estado</th>
          <th scope="col" class="px-5 py-3 text-left">Registro</th>
          <th scope="col" class="px-5 py-3 text-right">Acciones</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-warm-100">
        <?php if (empty($users)): ?>
        <tr>
          <td colspan="6" class="px-5 py-16 text-center">
            <i data-lucide="users" class="w-12 h-12 text-warm-200 mx-auto mb-3"></i>
            <p class="text-warm-400 font-medium">No se encontraron usuarios</p>
            <?php if ($hasFilters): ?>
            <a href="/admin/usuarios" class="text-sm text-brand-400 hover:underline mt-2 inline-block">Limpiar filtros</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php else: ?>
        <?php foreach ($users as $u): ?>
        <?php
          $isSelf = (int)($cu['id'] ?? 0) === (int)$u['id'];
          $initials = mb_strtoupper(mb_substr($u['name'], 0, 1)) . (str_contains($u['name'], ' ') ? mb_strtoupper(mb_substr(strstr($u['name'], ' '), 1, 1)) : '');
        ?>
        <tr class="hover:bg-warm-50/50 transition"
            x-data="{ active: <?= $u['active'] ? 'true' : 'false' ?>, busy: false }">
          <!-- Avatar + Name + Email -->
          <td class="px-5 py-3">
            <div class="flex items-center gap-3">
              <?php if ($u['avatar_url']): ?>
              <img src="<?= e($u['avatar_url']) ?>" alt="<?= e($u['name']) ?>"
                   class="w-9 h-9 rounded-full object-cover flex-shrink-0">
              <?php else: ?>
              <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                   style="background:var(--color-navy-dark)"><?= e($initials) ?></div>
              <?php endif; ?>
              <div>
                <a href="/admin/usuarios/<?= $u['id'] ?>"
                   class="font-medium text-warm-900 hover:text-brand-800 transition"><?= e($u['name']) ?></a>
                <p class="text-xs text-warm-400"><?= e($u['email']) ?></p>
              </div>
            </div>
          </td>
          <!-- Role badge -->
          <td class="px-5 py-3">
            <span class="text-xs font-medium px-2.5 py-1 rounded-full"
                  style="<?= $roleColors[$u['role']] ?? '' ?>">
              <?= e($roleLabels[$u['role']] ?? $u['role']) ?>
            </span>
          </td>
          <!-- Auth provider -->
          <td class="px-5 py-3">
            <span class="flex items-center gap-1.5 text-xs text-warm-500">
              <?php if ($u['auth_provider'] === 'google'): ?>
              <i data-lucide="chrome" class="w-3.5 h-3.5"></i> Google
              <?php else: ?>
              <i data-lucide="mail" class="w-3.5 h-3.5"></i> Propio
              <?php endif; ?>
            </span>
          </td>
          <!-- Status toggle -->
          <td class="px-5 py-3">
            <?php if ($isSelf): ?>
            <span class="text-xs text-warm-400 italic">Tú</span>
            <?php else: ?>
            <div class="flex items-center gap-2.5">
              <span class="text-xs font-semibold"
                    :class="active ? 'text-green-500' : 'text-red-500'"
                    x-text="active ? 'Activo' : 'Inactivo'"></span>
              <button type="button"
                      role="switch"
                      :aria-checked="active.toString()"
                      aria-label="Activar o desactivar usuario"
                      :disabled="busy"
                      class="relative inline-flex rounded-full transition-colors duration-200 ease-in-out focus:outline-none flex-shrink-0 disabled:opacity-60"
                      :class="active ? 'bg-green-500' : 'bg-red-500'"
                      style="width:52px;height:28px;padding:3px;"
                      @click="
                        busy = true;
                        fetch('/admin/usuarios/<?= $u['id'] ?>/toggle-status', { method:'POST',
                          headers:{'Content-Type':'application/json','X-CSRF-Token':document.querySelector('meta[name=csrf-token]')?.content??''}
                        })
                        .then(r=>r.json())
                        .then(d=>{ if(d.success){ active = d.active===1; } })
                        .catch(()=>{})
                        .finally(()=>{ busy=false; })
                      ">
                <span class="block rounded-full bg-white transition-transform duration-200 ease-in-out"
                      :style="{ transform: active ? 'translateX(24px)' : 'translateX(0)', width: '22px', height: '22px', boxShadow: '0 1px 3px rgba(0,0,0,0.25)' }"></span>
              </button>
            </div>
            <?php endif; ?>
          </td>
          <!-- Registration date -->
          <td class="px-5 py-3 text-xs text-warm-500">
            <?= date('d/m/Y', strtotime($u['created_at'])) ?>
          </td>
          <!-- Actions -->
          <td class="px-5 py-3">
            <div class="flex items-center justify-end gap-2">
              <a href="/admin/usuarios/<?= $u['id'] ?>"
                 aria-label="Ver usuario"
                 class="p-1.5 rounded-lg text-warm-400 hover:text-brand-800 hover:bg-warm-100 transition">
                <i data-lucide="eye" class="w-4 h-4"></i>
              </a>
              <a href="/admin/usuarios/<?= $u['id'] ?>/editar"
                 aria-label="Editar usuario"
                 class="p-1.5 rounded-lg text-warm-400 hover:text-brand-800 hover:bg-warm-100 transition">
                <i data-lucide="pencil" class="w-4 h-4"></i>
              </a>
              <?php if (!$isSelf): ?>
              <button type="button"
                      aria-label="Eliminar usuario"
                      class="p-1.5 rounded-lg text-warm-400 hover:text-red-600 hover:bg-red-50 transition"
                      @click="$dispatch('open-delete-modal', { id: <?= $u['id'] ?>, name: '<?= e(addslashes($u['name'])) ?>' })">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
              </button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($pager['total_pages'] > 1 || $pager['total'] > 0): ?>
  <div class="mt-5 flex flex-col sm:flex-row items-center justify-between gap-3">
    <p class="text-xs text-warm-400">
      Mostrando <?= ($pager['offset'] + 1) ?>–<?= min($pager['offset'] + $pager['per_page'], $pager['total']) ?>
      de <?= $pager['total'] ?> usuarios
    </p>
    <?php if ($pager['total_pages'] > 1): ?>
    <div class="flex items-center gap-1">
      <!-- Previous -->
      <?php if ($pager['has_prev']): ?>
      <a href="<?= e(usersUrl(['page' => $pager['page'] - 1])) ?>"
         class="px-3 py-1.5 rounded-lg text-sm border border-warm-200 hover:border-brand-400 transition">←</a>
      <?php else: ?>
      <span class="px-3 py-1.5 rounded-lg text-sm border border-warm-100 text-warm-300 cursor-not-allowed">←</span>
      <?php endif; ?>
      <!-- Pages -->
      <?php for ($p = 1; $p <= $pager['total_pages']; $p++): ?>
        <?php if ($p === $pager['page']): ?>
        <span class="px-3 py-1.5 rounded-lg text-sm font-bold text-white" style="background:var(--color-navy-deeper)"><?= $p ?></span>
        <?php elseif ($p === 1 || $p === $pager['total_pages'] || abs($p - $pager['page']) <= 2): ?>
        <a href="<?= e(usersUrl(['page' => $p])) ?>"
           class="px-3 py-1.5 rounded-lg text-sm border border-warm-200 hover:border-brand-400 transition"><?= $p ?></a>
        <?php elseif (abs($p - $pager['page']) === 3): ?>
        <span class="px-1 text-warm-300">…</span>
        <?php endif; ?>
      <?php endfor; ?>
      <!-- Next -->
      <?php if ($pager['has_next']): ?>
      <a href="<?= e(usersUrl(['page' => $pager['page'] + 1])) ?>"
         class="px-3 py-1.5 rounded-lg text-sm border border-warm-200 hover:border-brand-400 transition">→</a>
      <?php else: ?>
      <span class="px-3 py-1.5 rounded-lg text-sm border border-warm-100 text-warm-300 cursor-not-allowed">→</span>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Delete confirmation modal -->
  <div x-data="{
         open: false,
         userId: 0,
         userName: '',
         init() {
           window.addEventListener('open-delete-modal', e => {
             this.userId   = e.detail.id;
             this.userName = e.detail.name;
             this.open = true;
           });
         }
       }"
       x-show="open"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       @keydown.escape.window="open = false"
       role="dialog"
       aria-modal="true"
       aria-labelledby="modal-title"
       class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
       x-cloak>
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6" @click.stop>
      <h2 id="modal-title" class="text-lg font-semibold text-warm-900 mb-2">Confirmar eliminación</h2>
      <p class="text-sm text-warm-500 mb-6">
        ¿Confirmás que querés eliminar a <strong x-text="userName"></strong>?
        Esta acción no se puede deshacer.
      </p>
      <div class="flex justify-end gap-3">
        <button type="button"
                @click="open = false"
                class="px-4 py-2 rounded-xl text-sm border border-warm-200 text-warm-700 hover:bg-warm-50 transition">
          Cancelar
        </button>
        <!-- Hidden form submitted on confirm -->
        <form method="POST" :action="'/admin/usuarios/' + userId + '/eliminar'" x-ref="deleteForm">
          <input type="hidden" name="confirm_id" :value="userId">
        </form>
        <button type="button"
                @click="$refs.deleteForm.submit()"
                class="px-4 py-2 rounded-xl text-sm font-medium bg-red-600 text-white hover:bg-red-700 transition">
          Eliminar
        </button>
      </div>
    </div>
  </div>

</div>
