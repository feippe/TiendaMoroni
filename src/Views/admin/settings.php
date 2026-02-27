<?php $layout = 'layout/admin'; ?>

<div class="max-w-2xl">

  <!-- Page header -->
  <div class="flex items-center gap-3 mb-8">
    <i data-lucide="settings" class="w-6 h-6 text-brand-400"></i>
    <h1 class="text-xl font-semibold text-warm-900">Configuración del sitio</h1>
  </div>

  <!-- Settings card -->
  <div class="bg-white rounded-2xl border border-warm-200 divide-y divide-warm-100"
       x-data="{
         maintenance: <?= $maintenanceMode ? 'true' : 'false' ?>,
         toast: false,
         toastMsg: '',
         async toggle() {
           const newVal = this.maintenance ? '0' : '1';
           try {
             const res = await fetch('/admin/configuracion/toggle', {
               method: 'POST',
               headers: {
                 'Content-Type': 'application/json',
                 'X-CSRF-Token': document.querySelector('meta[name=csrf-token]')?.content ?? ''
               },
               body: JSON.stringify({ key: 'maintenance_mode', value: newVal })
             });
             const data = await res.json();
             if (data.success) {
               this.maintenance = newVal === '1';
               this.showToast('Configuración guardada');
             } else {
               this.showToast('Error: ' + (data.error ?? 'desconocido'));
             }
           } catch (e) {
             this.showToast('Error de conexión');
           }
         },
         showToast(msg) {
           this.toastMsg = msg;
           this.toast = true;
           setTimeout(() => this.toast = false, 3000);
         }
       }">

    <!-- Toast notification -->
    <div x-show="toast"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed bottom-6 right-6 z-50 bg-warm-900 text-warm-50 text-sm px-4 py-3 rounded-xl shadow-lg"
         x-cloak>
      <span x-text="toastMsg"></span>
    </div>

    <!-- Row: Modo mantenimiento -->
    <div class="flex items-center justify-between gap-6 px-6 py-5">

      <!-- Left: label + description -->
      <div class="flex-1 min-w-0">
        <p class="font-medium text-warm-900 text-sm">Modo mantenimiento</p>
        <p class="text-xs text-warm-400 mt-1 leading-relaxed">
          Cuando está activo, los visitantes ven una página de mantenimiento.
          Vos seguís viendo el sitio normalmente.
        </p>
      </div>

      <!-- Right: toggle switch + status text -->
      <div class="flex items-center gap-3 flex-shrink-0">
        <span class="text-xs font-medium"
              :class="maintenance ? 'text-accent' : 'text-warm-400'"
              x-text="maintenance ? 'Activo' : 'Inactivo'"></span>

        <!-- Pill toggle -->
        <button type="button"
                role="switch"
                :aria-checked="maintenance.toString()"
                aria-label="Activar o desactivar modo mantenimiento"
                @click="toggle()"
                class="relative inline-flex items-center rounded-full transition-colors duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
                :class="maintenance ? 'bg-accent' : 'bg-warm-300'"
                style="width:48px;height:26px;">
          <span class="absolute left-0.5 top-0.5 inline-block rounded-full bg-white shadow transition-transform duration-200"
                :style="maintenance ? 'transform:translateX(22px)' : 'transform:translateX(0)'"
                style="width:22px;height:22px;"></span>
        </button>
      </div>
    </div>

  </div>

</div>
