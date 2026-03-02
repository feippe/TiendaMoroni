<?php $layout = 'layout/admin'; ?>

<div class="max-w-2xl space-y-8">

  <!-- ── Page header ─────────────────────────────────────────────────────── -->
  <div>
    <h1 class="text-xl font-semibold text-warm-900">Configuración del sitio</h1>
    <p class="text-sm text-warm-400 mt-1">Administrá el comportamiento del sitio y los parámetros de correo.</p>
  </div>

  <!-- ── CARD 1: Sitio ───────────────────────────────────────────────────── -->
  <section>
    <p class="text-xs font-bold text-warm-400 uppercase tracking-widest mb-3">Sitio</p>
    <div class="bg-white rounded-2xl border border-warm-200 divide-y divide-warm-100"
         x-data="{
           maintenance: <?= $maintenanceMode ? 'true' : 'false' ?>,
           saving: false,
           toast: false, toastOk: true, toastMsg: '',
           async toggle() {
             this.saving = true;
             const newVal = this.maintenance ? '0' : '1';
             try {
               const res  = await fetch('/admin/configuracion/toggle', {
                 method: 'POST',
                 headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name=csrf-token]')?.content ?? '' },
                 body: JSON.stringify({ key: 'maintenance_mode', value: newVal })
               });
               const data = await res.json();
               if (data.success) {
                 this.maintenance = newVal === '1';
                 Alpine.store('siteStatus').maintenance = this.maintenance;
                 this.showToast('Modo ' + (this.maintenance ? 'mantenimiento activado' : 'online restaurado'), true);
               } else {
                 this.showToast('Error: ' + (data.error ?? 'desconocido'), false);
               }
             } catch { this.showToast('Error de conexión', false); }
             finally  { this.saving = false; }
           },
           showToast(msg, ok) {
             this.toastMsg = msg; this.toastOk = ok; this.toast = true;
             setTimeout(() => this.toast = false, 3000);
           }
         }">

      <!-- Row: Modo mantenimiento -->
      <div class="flex items-center gap-6 px-6 py-5">
        <div class="flex-1 min-w-0">
          <p class="font-medium text-warm-900 text-sm">Estado del sitio web</p>
          <p class="text-xs text-warm-400 mt-0.5 leading-relaxed">
            Este switch cambia el estado del sitio web entre online y offline.<br>
            Cuando está offline los visitantes verán una página de mantenimiento, y solo tú podrás ver el sitio completo.
          </p>
        </div>
        <div class="flex items-center gap-3 flex-shrink-0">
          <span class="text-xs font-semibold tabular-nums"
                :class="maintenance ? 'text-red-500' : 'text-green-500'"
                x-text="maintenance ? 'Offline' : 'Online'"></span>
          <button type="button" role="switch"
                  :aria-checked="maintenance.toString()"
                  :disabled="saving"
                  @click="toggle()"
                  class="relative inline-flex rounded-full transition-colors duration-200 ease-in-out focus:outline-none disabled:opacity-60 flex-shrink-0"
                  :class="maintenance ? 'bg-red-500' : 'bg-green-500'"
                  style="width:52px;height:28px;padding:3px;">
            <span class="block rounded-full bg-white transition-transform duration-200 ease-in-out"
                  :style="{ transform: maintenance ? 'translateX(24px)' : 'translateX(0)', width: '22px', height: '22px', boxShadow: '0 1px 3px rgba(0,0,0,0.25)' }"></span>
          </button>
        </div>
      </div>

      <!-- Toast -->
      <div x-show="toast" x-cloak
           x-transition:enter="transition ease-out duration-200"
           x-transition:enter-start="opacity-0 translate-y-1"
           x-transition:enter-end="opacity-100 translate-y-0"
           x-transition:leave="transition ease-in duration-150"
           x-transition:leave-end="opacity-0"
           class="fixed bottom-6 right-6 z-50 flex items-center gap-2.5 text-sm px-4 py-3 rounded-xl shadow-lg text-white"
           :class="toastOk ? 'bg-warm-900' : 'bg-red-600'">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" x-show="toastOk"  d="M5 13l4 4L19 7"/>
          <path stroke-linecap="round" stroke-linejoin="round" x-show="!toastOk" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        <span x-text="toastMsg"></span>
      </div>
    </div>
  </section>

  <!-- ── CARD 2: Servidor de correo ─────────────────────────────────────── -->
  <section x-data="smtpForm()" x-init="init()">
    <div class="flex items-center justify-between mb-3">
      <p class="text-xs font-bold text-warm-400 uppercase tracking-widest">Servidor de correo</p>
      <!-- Live connection indicator -->
      <span x-show="testStatus !== 'idle'" x-cloak
            class="flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full"
            :class="{
              'bg-green-50 text-green-600': testStatus === 'ok',
              'bg-red-50 text-red-500':    testStatus === 'error',
              'bg-warm-100 text-warm-500': testStatus === 'testing'
            }">
        <span x-show="testStatus === 'testing'" class="w-2 h-2 rounded-full bg-warm-400 animate-pulse"></span>
        <span x-show="testStatus === 'ok'"      class="w-2 h-2 rounded-full bg-green-500"></span>
        <span x-show="testStatus === 'error'"   class="w-2 h-2 rounded-full bg-red-500"></span>
        <span x-text="testStatus === 'testing' ? 'Enviando...' : testStatus === 'ok' ? testMsg : testMsg"></span>
      </span>
    </div>

    <div class="bg-white rounded-2xl border border-warm-200 overflow-hidden">

      <!-- ── Driver selector ──────────────────────────────────────────── -->
      <div class="px-6 pt-6 pb-4 border-b border-warm-100">
        <label class="block text-xs font-semibold text-warm-500 uppercase tracking-wider mb-2">Método de envío</label>
        <div class="inline-flex rounded-xl border border-warm-200 overflow-hidden text-sm">
          <button type="button" @click="form.driver = 'smtp'"
                  class="px-5 py-2 font-medium transition"
                  :class="form.driver === 'smtp' ? 'bg-brand-800 text-white' : 'text-warm-500 hover:bg-warm-50'">
            SMTP
          </button>
          <button type="button" @click="form.driver = 'mail'"
                  class="px-5 py-2 font-medium transition border-l border-warm-200"
                  :class="form.driver === 'mail' ? 'bg-brand-800 text-white' : 'text-warm-500 hover:bg-warm-50'">
            PHP mail()
          </button>
        </div>
        <p class="text-xs text-warm-400 mt-2"
           x-text="form.driver === 'smtp' ? 'Usa un servidor SMTP externo (recomendado). Compatible con iCloud, Gmail, SendGrid, etc.' : 'Usa la función mail() del servidor. Solo recomendado para entornos locales.'">
        </p>
      </div>

      <!-- ── SMTP fields (collapsed when driver = mail) ───────────────── -->
      <div x-show="form.driver === 'smtp'"
           x-transition:enter="transition ease-out duration-200"
           x-transition:enter-start="opacity-0 -translate-y-1"
           x-transition:enter-end="opacity-100 translate-y-0"
           x-transition:leave="transition ease-in duration-150"
           x-transition:leave-end="opacity-0"
           class="divide-y divide-warm-100">

        <!-- Sección: Conexión -->
        <div class="px-6 py-5">
          <p class="text-xs font-bold text-warm-300 uppercase tracking-widest mb-4">Conexión</p>
          <div class="grid grid-cols-1 sm:grid-cols-[1fr_120px] gap-4">

            <!-- Host -->
            <div>
              <label class="block text-xs font-semibold text-warm-600 mb-1.5">
                Servidor SMTP
                <span class="font-normal text-warm-400">(host)</span>
              </label>
              <input type="text" x-model="form.host"
                     placeholder="smtp.mail.me.com"
                     autocomplete="off" spellcheck="false"
                     class="w-full px-3.5 py-2.5 text-sm border border-warm-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-400/30 focus:border-brand-400 transition bg-warm-50 placeholder-warm-300">
            </div>

            <!-- Port -->
            <div>
              <label class="block text-xs font-semibold text-warm-600 mb-1.5">
                Puerto
              </label>
              <div class="relative">
                <input type="number" x-model="form.port"
                       placeholder="587"
                       min="1" max="65535"
                       class="w-full px-3.5 py-2.5 text-sm border border-warm-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-400/30 focus:border-brand-400 transition bg-warm-50 placeholder-warm-300">
              </div>
              <!-- Port presets -->
              <div class="flex gap-1.5 mt-2">
                <button type="button" @click="form.port = '587'"
                        class="text-xs px-2 py-0.5 rounded-md border transition"
                        :class="form.port == 587 ? 'border-brand-400 text-brand-400 bg-brand-50' : 'border-warm-200 text-warm-400 hover:border-warm-300'">
                  587 TLS
                </button>
                <button type="button" @click="form.port = '465'"
                        class="text-xs px-2 py-0.5 rounded-md border transition"
                        :class="form.port == 465 ? 'border-brand-400 text-brand-400 bg-brand-50' : 'border-warm-200 text-warm-400 hover:border-warm-300'">
                  465 SSL
                </button>
                <button type="button" @click="form.port = '25'"
                        class="text-xs px-2 py-0.5 rounded-md border transition"
                        :class="form.port == 25  ? 'border-brand-400 text-brand-400 bg-brand-50' : 'border-warm-200 text-warm-400 hover:border-warm-300'">
                  25
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Sección: Autenticación -->
        <div class="px-6 py-5">
          <p class="text-xs font-bold text-warm-300 uppercase tracking-widest mb-4">Autenticación</p>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            <!-- User -->
            <div>
              <label class="block text-xs font-semibold text-warm-600 mb-1.5">Usuario / Email</label>
              <input type="email" x-model="form.user"
                     placeholder="vos@icloud.com"
                     autocomplete="off"
                     class="w-full px-3.5 py-2.5 text-sm border border-warm-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-400/30 focus:border-brand-400 transition bg-warm-50 placeholder-warm-300">
            </div>

            <!-- Password -->
            <div>
              <label class="block text-xs font-semibold text-warm-600 mb-1.5">Contraseña / App Password</label>
              <div class="relative">
                <input :type="showPass ? 'text' : 'password'"
                       x-model="form.pass"
                       placeholder="Dejá vacío para no cambiar"
                       autocomplete="new-password"
                       class="w-full pl-3.5 pr-10 py-2.5 text-sm border border-warm-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-400/30 focus:border-brand-400 transition bg-warm-50 placeholder-warm-300">
                <button type="button" @click="showPass = !showPass"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-warm-400 hover:text-warm-700 transition">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          x-show="!showPass"
                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    <path stroke-linecap="round" stroke-linejoin="round"
                          x-show="showPass" x-cloak
                          d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M3 3l18 18"/>
                  </svg>
                </button>
              </div>
              <p class="text-xs text-warm-400 mt-1.5">Para iCloud usá una <em>App-specific password</em>.</p>
            </div>
          </div>
        </div>

      </div><!-- /smtp fields -->

      <!-- Sección: Remitente (always visible) -->
      <div class="px-6 py-5 border-t border-warm-100">
        <p class="text-xs font-bold text-warm-300 uppercase tracking-widest mb-4">Remitente</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

          <!-- From email -->
          <div>
            <label class="block text-xs font-semibold text-warm-600 mb-1.5">Email del remitente</label>
            <input type="email" x-model="form.from"
                   placeholder="hola@tiendamoroni.com"
                   class="w-full px-3.5 py-2.5 text-sm border border-warm-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-400/30 focus:border-brand-400 transition bg-warm-50 placeholder-warm-300">
            <p class="text-xs text-warm-400 mt-1.5">El "From:" que verán tus clientes.</p>
          </div>

          <!-- From name -->
          <div>
            <label class="block text-xs font-semibold text-warm-600 mb-1.5">Nombre del remitente</label>
            <input type="text" x-model="form.from_name"
                   placeholder="Tienda Moroni"
                   class="w-full px-3.5 py-2.5 text-sm border border-warm-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-400/30 focus:border-brand-400 transition bg-warm-50 placeholder-warm-300">
          </div>
        </div>
      </div>

      <!-- ── Footer: acciones ─────────────────────────────────────────── -->
      <div class="px-6 py-4 bg-warm-50 border-t border-warm-100 flex flex-wrap items-center justify-between gap-3">

        <!-- Test button -->
        <button type="button" @click="sendTest()"
                :disabled="testStatus === 'testing' || saving"
                x-show="form.driver === 'smtp'"
                class="flex items-center gap-2 text-sm text-warm-600 hover:text-warm-900 disabled:opacity-50 transition">
          <svg class="w-4 h-4" :class="testStatus === 'testing' && 'animate-spin'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
          </svg>
          Enviar email de prueba
        </button>
        <div x-show="form.driver !== 'smtp'" class="flex-1"></div>

        <!-- Save button -->
        <button type="button" @click="save()"
                :disabled="saving"
                class="flex items-center gap-2 px-5 py-2.5 bg-brand-800 text-white text-sm font-semibold rounded-xl hover:bg-brand-700 disabled:opacity-60 transition">
          <svg x-show="saving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
          </svg>
          <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
          </svg>
          <span x-text="saving ? 'Guardando...' : 'Guardar cambios'"></span>
        </button>
      </div>
    </div>

    <!-- Toast SMTP -->
    <div x-show="smtpToast" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-end="opacity-0"
         class="fixed bottom-6 right-6 z-50 flex items-center gap-2.5 text-sm px-4 py-3 rounded-xl shadow-lg text-white"
         :class="smtpToastOk ? 'bg-warm-900' : 'bg-red-600'">
      <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" x-show="smtpToastOk"  d="M5 13l4 4L19 7"/>
        <path stroke-linecap="round" stroke-linejoin="round" x-show="!smtpToastOk" d="M6 18L18 6M6 6l12 12"/>
      </svg>
      <span x-text="smtpToastMsg"></span>
    </div>
  </section>

</div>

<script>
function smtpForm() {
  return {
    form: {
      driver:    <?= json_encode($smtp['driver'])    ?>,
      host:      <?= json_encode($smtp['host'])      ?>,
      port:      <?= json_encode($smtp['port'])      ?>,
      user:      <?= json_encode($smtp['user'])      ?>,
      pass:      '',
      from:      <?= json_encode($smtp['from'])      ?>,
      from_name: <?= json_encode($smtp['from_name']) ?>,
    },
    showPass:    false,
    saving:      false,
    testStatus:  'idle',   // idle | testing | ok | error
    testMsg:     '',
    smtpToast:   false,
    smtpToastOk: true,
    smtpToastMsg:'',

    init() {},

    csrf() {
      return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    },

    async save() {
      this.saving = true;
      try {
        const fd = new FormData();
        fd.append('_csrf',     this.csrf());
        fd.append('driver',    this.form.driver);
        fd.append('host',      this.form.host);
        fd.append('port',      this.form.port);
        fd.append('user',      this.form.user);
        fd.append('pass',      this.form.pass);
        fd.append('from',      this.form.from);
        fd.append('from_name', this.form.from_name);

        const res  = await fetch('/admin/configuracion/smtp', {
          method: 'POST',
          body: fd,
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();

        if (data.success) {
          this.form.pass = '';   // clear password field after save
          this.flash('Configuración de correo guardada', true);
        } else {
          this.flash('Error: ' + (data.error ?? 'desconocido'), false);
        }
      } catch { this.flash('Error de conexión', false); }
      finally  { this.saving = false; }
    },

    async sendTest() {
      this.testStatus = 'testing';
      this.testMsg    = '';
      try {
        const fd = new FormData();
        fd.append('_csrf', this.csrf());

        const res  = await fetch('/admin/configuracion/smtp/test', {
          method: 'POST',
          body: fd,
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();

        if (data.success) {
          this.testStatus = 'ok';
          this.testMsg    = data.message ?? 'Email enviado';
          this.flash(data.message ?? 'Email de prueba enviado ✓', true);
        } else {
          this.testStatus = 'error';
          this.testMsg    = data.error ?? 'Falló';
          this.flash('Error: ' + (data.error ?? 'no se pudo enviar'), false);
        }
      } catch (e) {
        this.testStatus = 'error';
        this.testMsg    = 'Error de conexión';
        this.flash('Error de conexión', false);
      }
      setTimeout(() => { if (this.testStatus !== 'idle') this.testStatus = 'idle'; }, 6000);
    },

    flash(msg, ok) {
      this.smtpToastMsg = msg;
      this.smtpToastOk  = ok;
      this.smtpToast    = true;
      setTimeout(() => this.smtpToast = false, 3500);
    },
  };
}
</script>
