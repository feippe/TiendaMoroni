<?php $layout = 'layout/admin'; ?>

<div x-data="mediaManager()"
     x-init="init()"
     @dragover.prevent="dragover = true"
     @dragleave.prevent="dragover = false"
     @drop.prevent="onDrop($event)">

  <!-- ── Header ─────────────────────────────────────────────────────────── -->
  <div class="flex flex-wrap items-start justify-between gap-4 mb-6">

    <!-- Stats -->
    <div class="flex items-center gap-4 flex-wrap">
      <div class="bg-white rounded-xl border border-warm-200 px-4 py-2.5 text-sm flex items-center gap-2.5">
        <svg class="w-4 h-4 text-brand-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <span class="text-warm-500">Imágenes:</span>
        <span class="font-semibold text-warm-900" x-text="stats.totalFiles"></span>
      </div>
      <div class="bg-white rounded-xl border border-warm-200 px-4 py-2.5 text-sm flex items-center gap-2.5">
        <svg class="w-4 h-4 text-brand-400" fill="currentColor" viewBox="0 0 24 24">
          <path d="M10 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/>
        </svg>
        <span class="text-warm-500">Carpetas:</span>
        <span class="font-semibold text-warm-900" x-text="stats.totalFolders"></span>
      </div>
      <div class="bg-white rounded-xl border border-warm-200 px-4 py-2.5 text-sm flex items-center gap-2.5">
        <svg class="w-4 h-4 text-brand-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
        </svg>
        <span class="text-warm-500">Tamaño:</span>
        <span class="font-semibold text-warm-900" x-text="formatBytes(stats.totalSize)"></span>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center gap-2">
      <button @click="promptNewFolder()"
              class="flex items-center gap-2 px-4 py-2.5 border border-warm-300 text-warm-700 rounded-xl text-sm font-medium hover:bg-warm-50 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Nueva carpeta
      </button>
      <label :class="uploading && 'opacity-60 pointer-events-none'"
             class="flex items-center gap-2 px-4 py-2.5 bg-brand-800 text-white rounded-xl text-sm font-semibold hover:bg-brand-700 transition cursor-pointer">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
        </svg>
        <span x-text="uploading ? 'Subiendo...' : 'Subir imágenes'"></span>
        <input type="file" accept="image/*" multiple class="hidden"
               @change="uploadFiles($event.target.files); $event.target.value=''">
      </label>
    </div>
  </div>

  <!-- ── Breadcrumb ──────────────────────────────────────────────────────── -->
  <nav class="flex items-center gap-1.5 text-sm mb-5 flex-wrap">
    <button @click="navigate(null)"
            :class="folderId === null ? 'text-warm-900 font-semibold' : 'text-warm-400 hover:text-warm-900'"
            class="transition flex items-center gap-1">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
      </svg>
      Inicio
    </button>
    <template x-for="crumb in breadcrumb" :key="crumb.id">
      <span class="flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5 text-warm-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
        <button @click="navigate(crumb.id)"
                class="text-warm-400 hover:text-warm-900 transition font-medium"
                x-text="crumb.name"></button>
      </span>
    </template>
  </nav>

  <!-- ── Drop zone overlay ───────────────────────────────────────────────── -->
  <div x-show="dragover"
       x-cloak
       class="fixed inset-0 z-50 flex items-center justify-center pointer-events-none"
       style="background:rgba(15,30,46,0.7)">
    <div class="bg-white rounded-2xl p-10 text-center shadow-2xl">
      <svg class="w-14 h-14 text-brand-400 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
      </svg>
      <p class="font-bold text-warm-900 text-lg">Soltá para subir</p>
      <p class="text-warm-400 text-sm mt-1">Las imágenes se suben a la carpeta actual</p>
    </div>
  </div>

  <!-- ── Upload progress bar ─────────────────────────────────────────────── -->
  <div x-show="uploading" x-cloak class="mb-4 bg-white border border-warm-200 rounded-xl px-5 py-3 flex items-center gap-3">
    <svg class="animate-spin w-4 h-4 text-brand-400 flex-shrink-0" fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
    </svg>
    <div class="flex-1">
      <div class="flex justify-between text-xs text-warm-500 mb-1">
        <span>Subiendo <span x-text="uploadDone"></span> de <span x-text="uploadTotal"></span>...</span>
        <span x-text="Math.round(uploadDone / uploadTotal * 100) + '%'"></span>
      </div>
      <div class="h-1.5 bg-warm-100 rounded-full overflow-hidden">
        <div class="h-full bg-brand-400 rounded-full transition-all duration-300"
             :style="'width:' + Math.round(uploadDone / uploadTotal * 100) + '%'"></div>
      </div>
    </div>
  </div>

  <!-- ── Main content ────────────────────────────────────────────────────── -->
  <div class="bg-white rounded-2xl border border-warm-200 overflow-hidden">

    <!-- Loading -->
    <div x-show="loading" class="flex items-center justify-center py-20 text-warm-400 gap-2 text-sm">
      <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
      </svg>
      Cargando...
    </div>

    <!-- Content -->
    <div x-show="!loading" class="p-6">

      <!-- Empty -->
      <div x-show="folders.length === 0 && files.length === 0"
           class="flex flex-col items-center justify-center py-20 text-warm-400">
        <svg class="w-16 h-16 mb-4 opacity-20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3 9h18M3 6h18"/>
        </svg>
        <p class="font-semibold text-warm-600 mb-1">Esta carpeta está vacía</p>
        <p class="text-sm">Subí imágenes o creá una carpeta</p>
      </div>

      <!-- Folders section -->
      <div x-show="folders.length > 0" class="mb-8">
        <p class="text-xs font-bold text-warm-400 uppercase tracking-widest mb-4">
          Carpetas (<span x-text="folders.length"></span>)
        </p>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
          <template x-for="folder in folders" :key="folder.id">
            <div class="group relative">
              <button @click="navigate(folder.id)"
                      class="w-full flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-transparent hover:border-brand-200 hover:bg-brand-50 transition">
                <svg class="w-12 h-12 text-brand-400" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M10 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/>
                </svg>
                <span class="text-xs text-warm-700 text-center w-full truncate font-medium" x-text="folder.name"></span>
              </button>
              <!-- Folder actions -->
              <div class="absolute top-1.5 right-1.5 hidden group-hover:flex gap-1">
                <button @click.stop="confirmDeleteFolder(folder)"
                        class="w-6 h-6 bg-red-500 text-white rounded-md flex items-center justify-center hover:bg-red-600 transition"
                        title="Eliminar carpeta">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>
            </div>
          </template>
        </div>
      </div>

      <!-- Files section -->
      <div x-show="files.length > 0">
        <div class="flex items-center justify-between mb-4">
          <p class="text-xs font-bold text-warm-400 uppercase tracking-widest">
            Imágenes (<span x-text="files.length"></span>)
          </p>
          <!-- View toggle -->
          <div class="flex items-center gap-1 bg-warm-100 rounded-lg p-1">
            <button @click="viewMode = 'grid'"
                    :class="viewMode === 'grid' ? 'bg-white shadow text-warm-900' : 'text-warm-400'"
                    class="p-1.5 rounded-md transition">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
              </svg>
            </button>
            <button @click="viewMode = 'list'"
                    :class="viewMode === 'list' ? 'bg-white shadow text-warm-900' : 'text-warm-400'"
                    class="p-1.5 rounded-md transition">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Grid view -->
        <div x-show="viewMode === 'grid'"
             class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
          <template x-for="file in files" :key="file.id">
            <div class="group relative">
              <div class="aspect-square rounded-xl overflow-hidden bg-warm-100 border-2 border-transparent hover:border-brand-300 transition cursor-pointer"
                   @click="preview(file)">
                <img :src="file.url" :alt="file.filename" loading="lazy"
                     class="w-full h-full object-cover">
              </div>
              <!-- File info -->
              <p class="mt-1 text-xs text-warm-500 truncate" x-text="file.filename"></p>
              <p class="text-xs text-warm-400" x-text="formatBytes(parseInt(file.size))"></p>
              <!-- Delete button -->
              <div class="absolute top-1.5 right-1.5 hidden group-hover:flex">
                <button @click.stop="confirmDeleteFile(file)"
                        class="w-6 h-6 bg-red-500 text-white rounded-md flex items-center justify-center hover:bg-red-600 transition"
                        title="Eliminar imagen">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>
              <!-- Copy URL button -->
              <div class="absolute top-1.5 left-1.5 hidden group-hover:flex">
                <button @click.stop="copyUrl(file.url)"
                        class="w-6 h-6 bg-warm-800 text-white rounded-md flex items-center justify-center hover:bg-warm-900 transition"
                        title="Copiar URL">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                  </svg>
                </button>
              </div>
            </div>
          </template>
        </div>

        <!-- List view -->
        <div x-show="viewMode === 'list'" class="border border-warm-200 rounded-xl overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-warm-50 text-xs text-warm-500 uppercase tracking-wider">
              <tr>
                <th class="px-4 py-3 text-left">Imagen</th>
                <th class="px-4 py-3 text-left">Nombre</th>
                <th class="px-4 py-3 text-left hidden sm:table-cell">Tipo</th>
                <th class="px-4 py-3 text-left hidden md:table-cell">Tamaño</th>
                <th class="px-4 py-3 text-left hidden lg:table-cell">URL</th>
                <th class="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-warm-100">
              <template x-for="file in files" :key="'list_' + file.id">
                <tr class="hover:bg-warm-50 transition">
                  <td class="px-4 py-2.5">
                    <img :src="file.url" :alt="file.filename" loading="lazy"
                         class="w-12 h-12 object-cover rounded-lg border border-warm-200 cursor-pointer"
                         @click="preview(file)">
                  </td>
                  <td class="px-4 py-2.5 font-medium text-warm-900 max-w-[180px]">
                    <p class="truncate" x-text="file.filename"></p>
                  </td>
                  <td class="px-4 py-2.5 text-warm-500 hidden sm:table-cell" x-text="file.mime_type?.replace('image/','')"></td>
                  <td class="px-4 py-2.5 text-warm-500 hidden md:table-cell" x-text="formatBytes(parseInt(file.size))"></td>
                  <td class="px-4 py-2.5 hidden lg:table-cell">
                    <div class="flex items-center gap-2 max-w-[200px]">
                      <span class="text-warm-400 text-xs truncate" x-text="file.url"></span>
                      <button @click="copyUrl(file.url)"
                              class="flex-shrink-0 text-warm-400 hover:text-warm-900 transition"
                              title="Copiar URL">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                      </button>
                    </div>
                  </td>
                  <td class="px-4 py-2.5 text-right">
                    <button @click="confirmDeleteFile(file)"
                            class="text-red-400 hover:text-red-600 transition p-1 rounded"
                            title="Eliminar">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                      </svg>
                    </button>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>

  <!-- ── Image preview lightbox ──────────────────────────────────────────── -->
  <div x-show="previewFile"
       x-cloak
       @click.self="previewFile = null"
       @keydown.escape.window="previewFile = null"
       class="fixed inset-0 z-50 flex items-center justify-center p-4"
       style="background:rgba(0,0,0,.85)">
    <div class="relative max-w-4xl w-full" @click.stop>
      <button @click="previewFile = null"
              class="absolute -top-10 right-0 text-white hover:text-warm-300 transition">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
      <img :src="previewFile?.url" :alt="previewFile?.filename"
           class="max-h-[80vh] w-full object-contain rounded-xl">
      <div class="mt-3 flex items-center justify-between text-white text-sm">
        <div>
          <p class="font-semibold" x-text="previewFile?.filename"></p>
          <p class="text-warm-400 text-xs mt-0.5" x-text="previewFile?.url"></p>
        </div>
        <div class="flex items-center gap-2">
          <button @click="copyUrl(previewFile?.url)"
                  class="flex items-center gap-1.5 px-3 py-1.5 bg-warm-700 text-white rounded-lg text-xs hover:bg-warm-600 transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            Copiar URL
          </button>
          <button @click="confirmDeleteFile(previewFile); previewFile = null"
                  class="flex items-center gap-1.5 px-3 py-1.5 bg-red-600 text-white rounded-lg text-xs hover:bg-red-700 transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Eliminar
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Copy toast ──────────────────────────────────────────────────────── -->
  <div x-show="copied"
       x-cloak
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0 translate-y-2"
       x-transition:enter-end="opacity-100 translate-y-0"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-warm-900 text-white text-sm px-5 py-2.5 rounded-xl shadow-xl flex items-center gap-2">
    <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
    </svg>
    URL copiada
  </div>

</div>

<script>
function mediaManager() {
  return {
    // ── State ──
    loading:     false,
    folderId:    <?= json_encode($folderId) ?>,
    breadcrumb:  <?= json_encode($breadcrumb) ?>,
    folders:     <?= json_encode(array_values($folders)) ?>,
    files:       <?= json_encode(array_values($files)) ?>,
    stats: {
      totalFiles:   <?= (int)$totalFiles ?>,
      totalFolders: <?= (int)$totalFolders ?>,
      totalSize:    <?= (int)$totalSize ?>,
    },
    uploading:   false,
    uploadDone:  0,
    uploadTotal: 0,
    dragover:    false,
    viewMode:    'grid',
    previewFile: null,
    copied:      false,

    // ── Init ──
    init() {
      // restore view preference
      const saved = localStorage.getItem('mediaViewMode');
      if (saved) this.viewMode = saved;
      this.$watch('viewMode', v => localStorage.setItem('mediaViewMode', v));
    },

    // ── Navigation ──
    async navigate(folderId) {
      this.folderId = folderId;
      await this.load();
      // Update URL without reload
      const url = folderId ? '?folder_id=' + folderId : '/admin/repositorio';
      history.pushState({}, '', url);
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
        console.error('load error:', e);
      } finally {
        this.loading = false;
      }
    },

    // ── Upload ──
    csrf() {
      return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    },

    onDrop(e) {
      this.dragover = false;
      const files = e.dataTransfer?.files;
      if (files?.length) this.uploadFiles(files);
    },

    async uploadFiles(fileList) {
      if (!fileList || fileList.length === 0) return;
      this.uploading   = true;
      this.uploadDone  = 0;
      this.uploadTotal = fileList.length;

      for (const file of Array.from(fileList)) {
        const fd = new FormData();
        fd.append('file',      file);
        fd.append('folder_id', this.folderId ?? '');
        fd.append('_csrf',     this.csrf());

        try {
          const res  = await fetch('/admin/media/subir', {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
          });
          const data = await res.json();
          if (data.success) {
            this.files.unshift(data.file);
            this.stats.totalFiles++;
            this.stats.totalSize += data.file.size ?? 0;
          } else {
            alert('Error al subir "' + file.name + '": ' + (data.message ?? 'error desconocido'));
          }
        } catch (e) {
          alert('Error de red al subir "' + file.name + '".');
          console.error(e);
        }
        this.uploadDone++;
      }

      this.uploading = false;
    },

    // ── Folder management ──
    async promptNewFolder() {
      const name = prompt('Nombre de la nueva carpeta:');
      if (!name?.trim()) return;

      const fd = new FormData();
      fd.append('name',      name.trim());
      fd.append('parent_id', this.folderId ?? '');
      fd.append('_csrf',     this.csrf());

      try {
        const res  = await fetch('/admin/media/carpeta', {
          method: 'POST',
          body: fd,
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        if (data.success) {
          this.folders.push(data.folder);
          this.stats.totalFolders++;
        } else {
          alert('Error: ' + (data.message ?? 'no se pudo crear la carpeta'));
        }
      } catch (e) {
        alert('Error de red al crear la carpeta.');
        console.error(e);
      }
    },

    async confirmDeleteFolder(folder) {
      if (!confirm('¿Eliminár la carpeta "' + folder.name + '" y todo su contenido?\nEsta acción no se puede deshacer.')) return;

      const fd = new FormData();
      fd.append('id',    folder.id);
      fd.append('_csrf', this.csrf());

      try {
        const res  = await fetch('/admin/media/carpeta/eliminar', {
          method: 'POST',
          body: fd,
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        if (data.success) {
          this.folders = this.folders.filter(f => f.id !== folder.id);
          this.stats.totalFolders--;
        } else {
          alert('Error: ' + (data.message ?? 'no se pudo eliminar'));
        }
      } catch (e) {
        alert('Error de red al eliminar la carpeta.');
        console.error(e);
      }
    },

    // ── File management ──
    async confirmDeleteFile(file) {
      if (!confirm('¿Eliminar "' + file.filename + '"?\nEsta acción no se puede deshacer.')) return;

      const fd = new FormData();
      fd.append('id',    file.id);
      fd.append('_csrf', this.csrf());

      try {
        const res  = await fetch('/admin/media/eliminar', {
          method: 'POST',
          body: fd,
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        if (data.success) {
          this.files = this.files.filter(f => f.id !== file.id);
          this.stats.totalFiles--;
          this.stats.totalSize -= file.size ?? 0;
        } else {
          alert('Error: ' + (data.message ?? 'no se pudo eliminar'));
        }
      } catch (e) {
        alert('Error de red al eliminar el archivo.');
        console.error(e);
      }
    },

    // ── Lightbox ──
    preview(file) {
      this.previewFile = file;
    },

    // ── Utility ──
    async copyUrl(url) {
      try {
        await navigator.clipboard.writeText(url);
      } catch {
        // Fallback for older browsers
        const el = document.createElement('textarea');
        el.value = url;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
      }
      this.copied = true;
      setTimeout(() => this.copied = false, 2000);
    },

    formatBytes(bytes) {
      if (!bytes || bytes === 0) return '0 B';
      const k = 1024;
      const sizes = ['B', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    },
  };
}
</script>
