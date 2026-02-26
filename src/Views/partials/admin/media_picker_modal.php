<!-- ════════════════════════════════════════════════════════════════
     Media Picker Modal — included once in admin layout
     Trigger from anywhere with:
       window.dispatchEvent(new CustomEvent('open-media-picker', { detail: { callback: fn } }))
     The callback receives: { id, filename, url }
════════════════════════════════════════════════════════════════ -->
<div id="media-picker"
     x-data="mediaPicker()"
     x-cloak
     x-show="open"
     @open-media-picker.window="openWith($event.detail)"
     @keydown.escape.window="open && close()"
     class="fixed inset-0 z-[200] flex items-center justify-center p-4"
     style="background:rgba(0,0,0,.6)">

  <div class="bg-white rounded-2xl shadow-2xl flex flex-col w-full max-w-4xl"
       style="height:82vh"
       @click.stop>

    <!-- Header -->
    <div class="flex items-start justify-between px-6 py-4 border-b border-warm-200 flex-shrink-0">
      <div>
        <h2 class="font-bold text-warm-900 text-lg">Repositorio de imágenes</h2>
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-1 text-xs text-warm-400 mt-1 flex-wrap">
          <button @click="navigate(null)"
                  class="hover:text-brand-700 transition font-medium">
            Inicio
          </button>
          <template x-for="crumb in breadcrumb" :key="crumb.id">
            <span class="flex items-center gap-1">
              <span class="opacity-40">/</span>
              <button @click="navigate(crumb.id)"
                      class="hover:text-brand-700 transition font-medium"
                      x-text="crumb.name"></button>
            </span>
          </template>
        </nav>
      </div>
      <button @click="close()" class="p-1 text-warm-400 hover:text-warm-900 transition mt-0.5">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <!-- Toolbar -->
    <div class="flex items-center gap-3 px-6 py-3 border-b border-warm-100 flex-shrink-0 flex-wrap">
      <label class="flex items-center gap-2 cursor-pointer px-4 py-2 bg-brand-700 text-white rounded-lg text-sm font-semibold hover:bg-brand-800 transition"
             :class="uploading && 'opacity-60 pointer-events-none'">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
        </svg>
        <span x-text="uploading ? 'Subiendo...' : 'Subir imágenes'"></span>
        <input type="file" accept="image/*" multiple class="hidden"
               @change="uploadFiles($event.target.files); $event.target.value=''">
      </label>

      <button @click="promptNewFolder()"
              class="flex items-center gap-2 px-4 py-2 border border-warm-300 text-warm-700 rounded-lg text-sm font-medium hover:bg-warm-50 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Nueva carpeta
      </button>

      <span x-show="uploading" class="text-xs text-warm-400 italic">
        Subiendo <span x-text="uploadingCount"></span> archivo(s)...
      </span>
    </div>

    <!-- Content -->
    <div class="flex-1 overflow-y-auto p-6">

      <!-- Loading -->
      <div x-show="loading" class="flex items-center justify-center h-32 text-warm-400 text-sm gap-2">
        <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
        </svg>
        Cargando...
      </div>

      <div x-show="!loading">

        <!-- Folders -->
        <div x-show="folders.length > 0" class="mb-6">
          <p class="text-xs font-semibold text-warm-400 uppercase tracking-wider mb-3">Carpetas</p>
          <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-3">
            <template x-for="folder in folders" :key="folder.id">
              <button @click="navigate(folder.id)"
                      class="flex flex-col items-center gap-1.5 p-3 rounded-xl hover:bg-brand-50 transition group border border-transparent hover:border-brand-200">
                <svg class="w-10 h-10 text-brand-400 group-hover:text-brand-600 transition" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M10 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/>
                </svg>
                <span class="text-xs text-warm-700 text-center w-full truncate" x-text="folder.name"></span>
              </button>
            </template>
          </div>
        </div>

        <!-- Files -->
        <div x-show="files.length > 0">
          <p class="text-xs font-semibold text-warm-400 uppercase tracking-wider mb-3">Imágenes</p>
          <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
            <template x-for="file in files" :key="file.id">
              <div class="group relative">
                <button @click="select(file)"
                        class="w-full aspect-square rounded-xl overflow-hidden bg-warm-100 border-2 border-transparent hover:border-brand-400 focus:border-brand-600 transition block">
                  <img :src="file.url" :alt="file.filename" loading="lazy"
                       class="w-full h-full object-cover">
                </button>
                <p class="mt-1 text-xs text-warm-500 truncate leading-tight" x-text="file.filename"></p>
              </div>
            </template>
          </div>
        </div>

        <!-- Empty -->
        <div x-show="folders.length === 0 && files.length === 0"
             class="flex flex-col items-center justify-center h-40 text-warm-400">
          <svg class="w-12 h-12 mb-3 opacity-30" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3 9h18M3 6h18"/>
          </svg>
          <p class="text-sm font-medium">Esta carpeta está vacía</p>
          <p class="text-xs mt-1">Subí imágenes o creá una subcarpeta</p>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
function mediaPicker() {
  return {
    open:          false,
    loading:       false,
    uploading:     false,
    uploadingCount: 0,
    folderId:      null,
    folders:       [],
    files:         [],
    breadcrumb:    [],
    callback:      null,

    openWith(detail) {
      this.callback = detail.callback ?? null;
      this.folderId = null;
      this.open     = true;
      this.load();
    },

    close() {
      this.open     = false;
      this.callback = null;
    },

    async navigate(folderId) {
      this.folderId = folderId;
      await this.load();
    },

    async load() {
      this.loading = true;
      try {
        const url = '/admin/media' + (this.folderId ? '?folder_id=' + this.folderId : '');
        const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();
        this.folders    = data.folders    ?? [];
        this.files      = data.files      ?? [];
        this.breadcrumb = data.breadcrumb ?? [];
      } catch (e) {
        console.error('mediaPicker.load error:', e);
      } finally {
        this.loading = false;
      }
    },

    async uploadFiles(fileList) {
      if (!fileList || fileList.length === 0) return;
      this.uploading     = true;
      this.uploadingCount = fileList.length;

      for (const file of fileList) {
        const fd = new FormData();
        fd.append('file',      file);
        fd.append('folder_id', this.folderId ?? '');
        fd.append('_csrf',     document.querySelector('meta[name="csrf-token"]')?.content ?? '');

        try {
          const res  = await fetch('/admin/media/subir', { method: 'POST', body: fd });
          const data = await res.json();
          if (data.success) {
            this.files.unshift(data.file);
          } else {
            alert('Error al subir "' + file.name + '": ' + (data.message ?? 'error desconocido'));
          }
        } catch (e) {
          console.error('upload error:', e);
        }
        this.uploadingCount--;
      }

      this.uploading = false;
    },

    async promptNewFolder() {
      const name = prompt('Nombre de la nueva carpeta:');
      if (!name?.trim()) return;

      const fd = new FormData();
      fd.append('name',      name.trim());
      fd.append('parent_id', this.folderId ?? '');
      fd.append('_csrf',     document.querySelector('meta[name="csrf-token"]')?.content ?? '');

      const res  = await fetch('/admin/media/carpeta', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        this.folders.push(data.folder);
      }
    },

    select(file) {
      if (this.callback) this.callback(file);
      this.close();
    },
  };
}
</script>
