<?php $layout = 'layout/admin'; ?>

<div class="mb-6 flex items-center gap-3">
  <a href="/admin/productos" class="text-warm-500 hover:text-warm-900 transition text-sm">← Volver</a>
  <h2 class="text-xl font-bold text-warm-900">
    <?= $product ? 'Editar: ' . e($product['name']) : 'Nuevo producto' ?>
  </h2>
</div>

<?php if (!empty($saved)): ?>
<div class="mb-5 bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">
  ✓ Producto guardado correctamente.
</div>
<?php endif; ?>

<form method="post"
      action="<?= $product ? '/admin/productos/' . (int)$product['id'] . '/actualizar' : '/admin/productos/guardar' ?>"
      class="grid grid-cols-1 lg:grid-cols-3 gap-6"
      x-data="productForm()">

  <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">

  <!-- Main form -->
  <div class="lg:col-span-2 space-y-6">

    <!-- Basic info -->
    <div class="bg-white rounded-2xl border border-warm-200 p-6 space-y-4">
      <h3 class="font-semibold text-warm-900">Información básica</h3>

      <div>
        <label class="block text-sm font-medium text-warm-700 mb-1">Nombre del producto *</label>
        <input type="text" name="name" required
               value="<?= e($product['name'] ?? '') ?>"
               @input="updateSlug($event.target.value)"
               class="w-full border border-warm-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
      </div>

      <div>
        <label class="block text-sm font-medium text-warm-700 mb-1">Slug (URL)</label>
        <input type="text" name="slug" x-model="slug"
               value="<?= e($product['slug'] ?? '') ?>"
               class="w-full border border-warm-300 rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
        <p class="text-xs text-warm-400 mt-1"><?= SITE_URL ?>/producto/<span x-text="slug" class="font-mono"></span></p>
      </div>

      <div>
        <label class="block text-sm font-medium text-warm-700 mb-1">Descripción corta</label>
        <textarea name="short_description" rows="2" maxlength="500"
                  class="w-full border border-warm-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition resize-none"
        ><?= e($product['short_description'] ?? '') ?></textarea>
      </div>

      <div>
        <label class="block text-sm font-medium text-warm-700 mb-1">Descripción completa (HTML permitido)</label>
        <textarea name="description" rows="6"
                  class="w-full border border-warm-300 rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-brand-400 transition"
        ><?= htmlspecialchars($product['description'] ?? '', ENT_NOQUOTES, 'UTF-8') ?></textarea>
      </div>
    </div>

    <!-- Pricing & inventory -->
    <div class="bg-white rounded-2xl border border-warm-200 p-6 space-y-4">
      <h3 class="font-semibold text-warm-900">Precio e inventario</h3>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-warm-700 mb-1">Precio (UYU) *</label>
          <input type="number" name="price" min="0" step="0.01" required
                 value="<?= e($product['price'] ?? '') ?>"
                 class="w-full border border-warm-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
        </div>
        <div>
          <label class="block text-sm font-medium text-warm-700 mb-1">Stock</label>
          <input type="number" name="stock" min="0"
                 value="<?= e($product['stock'] ?? 0) ?>"
                 class="w-full border border-warm-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
        </div>
      </div>
    </div>

    <!-- ── Image gallery (create + edit) ── -->
    <script>
      /* JSON embedded via script tag to avoid double-quote conflicts in x-data attribute */
      const __productId     = <?= $product ? (int)$product['id'] : 'null' ?>;
      const __productImages = <?= json_encode(array_values($images ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <div class="bg-white rounded-2xl border border-warm-200 p-6"
         id="image-manager-panel"
         x-data="imageManager(__productId, __productImages)">

      <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
          <h3 class="font-semibold text-warm-900">Imágenes del producto</h3>
          <p class="text-xs text-warm-400 mt-0.5">
            La primera imagen es la principal. Podés reordenarlas con las flechas.
            <?php if (!$product): ?><span class="text-brand-600 font-medium">Guardá el producto para que las imágenes queden asociadas.</span><?php endif; ?>
          </p>
        </div>
        <div class="flex items-center gap-2">
          <!-- Direct upload button -->
          <label class="flex items-center gap-1.5 px-3 py-2 border border-warm-300 text-warm-700 rounded-lg text-sm font-medium hover:bg-warm-50 transition cursor-pointer"
                 :class="uploading && 'opacity-60 pointer-events-none'">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            <span x-text="uploading ? 'Subiendo...' : 'Subir'"></span>
            <input type="file" accept="image/*" multiple class="hidden"
                   @change="uploadDirect($event.target.files); $event.target.value=''">
          </label>
          <!-- Pick from repository -->
          <button type="button"
                  @click="openPicker()"
                  class="flex items-center gap-1.5 px-3 py-2 bg-brand-800 text-white rounded-lg text-sm font-semibold hover:bg-brand-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Repositorio
          </button>
        </div>
      </div>

      <!-- Image list -->
      <div x-show="images.length > 0" class="space-y-2">
        <template x-for="(img, index) in images" :key="'img_' + img.id">
          <div class="flex items-center gap-3 bg-warm-50 rounded-xl p-3 border border-warm-200">
            <img :src="img.image_url" :alt="'Imagen ' + (index + 1)" loading="lazy"
                 class="w-16 h-16 rounded-lg object-cover flex-shrink-0 border border-warm-200"
                 onerror="this.style.background='#f5f5f0'">
            <div class="flex-1 min-w-0">
              <span x-show="index === 0"
                    class="inline-block text-xs font-semibold px-2 py-0.5 bg-brand-100 text-brand-800 rounded-full mb-1">
                Principal
              </span>
              <p class="text-xs text-warm-500 truncate" x-text="img.image_url.split('/').pop()"></p>
            </div>
            <div class="flex flex-col gap-1">
              <button type="button" @click="moveUp(index)" :disabled="index === 0"
                      class="p-1 rounded text-warm-400 hover:text-warm-900 hover:bg-warm-200 transition disabled:opacity-30 disabled:cursor-not-allowed">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
                </svg>
              </button>
              <button type="button" @click="moveDown(index)" :disabled="index === images.length - 1"
                      class="p-1 rounded text-warm-400 hover:text-warm-900 hover:bg-warm-200 transition disabled:opacity-30 disabled:cursor-not-allowed">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
              </button>
            </div>
            <button type="button" @click="removeImage(img, index)"
                    class="p-1.5 rounded-lg text-red-400 hover:text-red-600 hover:bg-red-50 transition">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </button>
          </div>
        </template>

        <!-- Hidden inputs for create mode — submitted with the form -->
        <?php if (!$product): ?>
        <template x-for="img in images" :key="'hi_' + img.id">
          <input type="hidden" name="images[]" :value="img.image_url">
        </template>
        <?php endif; ?>
      </div>

      <div x-show="images.length === 0"
           class="flex flex-col items-center justify-center py-10 text-warm-400 border-2 border-dashed border-warm-200 rounded-xl">
        <svg class="w-10 h-10 mb-2 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3 9h18M3 6h18"/>
        </svg>
        <p class="text-sm font-medium">Sin imágenes</p>
        <p class="text-xs mt-1">Subí archivos directamente o elegí del repositorio.</p>
      </div>

      <div class="flex items-center gap-3 mt-3 min-h-[1.5rem]">
        <p x-show="saving" class="text-xs text-warm-400 italic">Guardando orden...</p>
        <p x-show="savedMsg" x-cloak class="text-xs text-green-600">✓ Orden guardado</p>
        <p x-show="uploading" class="text-xs text-warm-400 italic">Subiendo imagen(es)...</p>
      </div>

      <!-- Remove confirmation dialog (edit mode only — needs product id to call API) -->
      <?php if ($product): ?>
      <div x-show="confirmDialog"
           x-cloak
           class="fixed inset-0 z-[300] flex items-center justify-center p-4"
           style="background:rgba(0,0,0,.5)">
        <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full" @click.stop>
          <h3 class="font-bold text-warm-900 mb-2">Quitar imagen</h3>
          <p class="text-sm text-warm-600 mb-5">¿Querés quitar esta imagen del producto? Podés también eliminarla del repositorio.</p>
          <div class="space-y-2">
            <button type="button" @click="doRemove(false)"
                    class="w-full py-2.5 px-4 bg-warm-100 text-warm-800 rounded-xl text-sm font-semibold hover:bg-warm-200 transition text-left">
              Quitar del producto <span class="font-normal text-warm-500">(mantener en repositorio)</span>
            </button>
            <button type="button" @click="doRemove(true)"
                    class="w-full py-2.5 px-4 bg-red-50 text-red-700 rounded-xl text-sm font-semibold hover:bg-red-100 transition text-left">
              Quitar del producto y eliminar del repositorio
            </button>
            <button type="button" @click="confirmDialog = false; pendingRemove = null"
                    class="w-full py-2.5 px-4 border border-warm-200 text-warm-600 rounded-xl text-sm hover:bg-warm-50 transition">
              Cancelar
            </button>
          </div>
        </div>
      </div>
      <?php endif; ?>

    </div>

    <!-- SEO -->
    <div class="bg-white rounded-2xl border border-warm-200 p-6 space-y-4">
      <h3 class="font-semibold text-warm-900">SEO</h3>
      <div>
        <label class="block text-sm font-medium text-warm-700 mb-1">Meta título</label>
        <input type="text" name="meta_title" maxlength="160"
               value="<?= e($product['meta_title'] ?? '') ?>"
               class="w-full border border-warm-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
      </div>
      <div>
        <label class="block text-sm font-medium text-warm-700 mb-1">Meta descripción</label>
        <textarea name="meta_description" rows="2" maxlength="320"
                  class="w-full border border-warm-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition resize-none"
        ><?= e($product['meta_description'] ?? '') ?></textarea>
      </div>
    </div>

  </div>

  <!-- Sidebar -->
  <div class="space-y-6">

    <!-- Status & publish -->
    <div class="bg-white rounded-2xl border border-warm-200 p-6 space-y-4">
      <h3 class="font-semibold text-warm-900">Publicación</h3>

      <div>
        <label class="block text-sm font-medium text-warm-700 mb-1">Estado</label>
        <select name="status"
                class="w-full border border-warm-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
          <?php foreach (['active'=>'Activo','draft'=>'Borrador','inactive'=>'Inactivo'] as $val => $label): ?>
          <option value="<?= $val ?>" <?= ($product['status'] ?? 'draft') === $val ? 'selected' : '' ?>>
            <?= $label ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="featured" value="1"
               <?= ($product['featured'] ?? 0) ? 'checked' : '' ?>
               class="rounded text-brand-600">
        <span class="text-sm text-warm-700">Producto destacado ⭐</span>
      </label>

      <div>
        <label class="block text-sm font-medium text-warm-700 mb-1">Categoría</label>
        <select name="category_id"
                class="w-full border border-warm-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
          <option value="">Sin categoría</option>
          <?php foreach ($categories as $cat): ?>
          <option value="<?= (int)$cat['id'] ?>"
                  <?= (int)($product['category_id'] ?? 0) === (int)$cat['id'] ? 'selected' : '' ?>>
            <?= e($cat['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-warm-700 mb-1">Vendedor *</label>
        <select name="vendor_id"
                class="w-full border border-warm-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
          <option value="">— Seleccionar vendedor —</option>
          <?php foreach ($vendors as $v): ?>
          <option value="<?= (int)$v['id'] ?>"
                  <?= (int)($product['vendor_id'] ?? 0) === (int)$v['id'] ? 'selected' : '' ?>>
            <?= e($v['business_name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <button type="submit"
              class="w-full bg-brand-800 text-white py-2.5 rounded-xl text-sm font-bold hover:bg-brand-700 transition">
        <?= $product ? 'Guardar cambios' : 'Crear producto' ?>
      </button>
    </div>

    <!-- Main image preview (unified for create + edit, updated by imageManager) -->
    <div class="bg-white rounded-2xl border border-warm-200 p-4">
      <p class="text-xs font-semibold text-warm-500 uppercase tracking-wider mb-3">Imagen principal</p>
      <div id="main-preview-wrap" class="relative">
        <img id="main-img-preview"
             src="<?= e($product['main_image_url'] ?? '') ?>"
             alt="Vista previa"
             loading="lazy"
             class="w-full rounded-xl object-cover aspect-square<?= empty($product['main_image_url'] ?? '') ? ' hidden' : '' ?>">
        <div id="main-preview-placeholder"
             class="w-full aspect-square rounded-xl bg-warm-100 flex flex-col items-center justify-center text-warm-300 gap-2<?= !empty($product['main_image_url'] ?? '') ? ' hidden' : '' ?>">
          <svg class="w-10 h-10 opacity-50" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3 9h18M3 6h18"/>
          </svg>
          <span class="text-xs">Sin imagen</span>
        </div>
      </div>
      <p class="text-xs text-warm-400 mt-2 text-center">La primera imagen de la galería es la principal.</p>
    </div>

  </div>

</form>

<script>
function productForm() {
  return {
    slug: '<?= e($product['slug'] ?? '') ?>',
    updateSlug(name) {
      this.slug = name
        .toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/[\s-]+/g, '-')
        .trim();
    }
  };
}

function imageManager(productId, initialImages) {
  return {
    productId,
    images:        JSON.parse(JSON.stringify(initialImages)),
    uploading:     false,
    saving:        false,
    savedMsg:      false,
    confirmDialog: false,
    pendingRemove: null,

    // ── helpers ──
    csrfToken() {
      return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    },

    // ── open repository picker ──
    openPicker() {
      window.dispatchEvent(new CustomEvent('open-media-picker', {
        detail: { callback: (file) => this.attachImage(file) }
      }));
    },

    // ── direct file upload ──
    async uploadDirect(fileList) {
      if (!fileList || fileList.length === 0) return;
      this.uploading = true;
      for (const file of Array.from(fileList)) {
        const fd = new FormData();
        fd.append('file',      file);
        fd.append('folder_id', '');
        fd.append('_csrf',     this.csrfToken());
        try {
          const res  = await fetch('/admin/media/subir', { method: 'POST', body: fd });
          const data = await res.json();
          if (data.success && data.file) {
            await this.attachImage(data.file);
          } else {
            alert('Error al subir: ' + (data.message ?? 'revise los logs del servidor.'));
          }
        } catch (e) {
          alert('Error de red al subir la imagen.');
        }
      }
      this.uploading = false;
    },

    // ── attach image (from picker or after upload) ──
    async attachImage(file) {
      if (this.productId) {
        // EDIT MODE — persist immediately via AJAX
        const fd = new FormData();
        fd.append('url',   file.url);
        fd.append('_csrf', this.csrfToken());
        try {
          const res  = await fetch('/admin/productos/' + this.productId + '/imagenes/agregar', { method: 'POST', body: fd });
          const data = await res.json();
          if (data.success) {
            this.images.push(data.image);
            this.updateMainPreview();
          } else {
            alert(data.message ?? 'Error al agregar la imagen.');
          }
        } catch (e) {
          alert('Error de red al agregar la imagen.');
        }
      } else {
        // CREATE MODE — accumulate client-side, submitted with hidden inputs
        this.images.push({
          id:         'new_' + Date.now() + '_' + Math.floor(Math.random() * 10000),
          image_url:  file.url,
          sort_order: this.images.length,
        });
        this.updateMainPreview();
      }
    },

    // ── reorder ──
    moveUp(index) {
      if (index === 0) return;
      [this.images[index - 1], this.images[index]] = [this.images[index], this.images[index - 1]];
      if (this.productId) this.saveOrder();
      this.updateMainPreview();
    },

    moveDown(index) {
      if (index === this.images.length - 1) return;
      [this.images[index], this.images[index + 1]] = [this.images[index + 1], this.images[index]];
      if (this.productId) this.saveOrder();
      this.updateMainPreview();
    },

    async saveOrder() {
      this.saving   = true;
      this.savedMsg = false;
      const fd = new FormData();
      fd.append('order', JSON.stringify(this.images.map(i => i.id)));
      fd.append('_csrf', this.csrfToken());
      try {
        await fetch('/admin/productos/' + this.productId + '/imagenes/orden', { method: 'POST', body: fd });
      } catch (e) { /* silent */ }
      this.saving   = false;
      this.savedMsg = true;
      setTimeout(() => this.savedMsg = false, 2500);
    },

    // ── remove ──
    removeImage(img, index) {
      if (!this.productId) {
        // CREATE MODE: instant remove, no confirm needed
        this.images.splice(index, 1);
        this.updateMainPreview();
      } else {
        // EDIT MODE: show confirm so user can also delete from repo
        this.pendingRemove = { img, index };
        this.confirmDialog = true;
      }
    },

    async doRemove(deleteFromRepo) {
      if (!this.pendingRemove) return;
      const { img, index } = this.pendingRemove;
      this.confirmDialog = false;
      this.pendingRemove = null;
      const fd = new FormData();
      fd.append('image_id',         img.id);
      fd.append('delete_from_repo', deleteFromRepo ? '1' : '0');
      fd.append('_csrf',            this.csrfToken());
      try {
        const res  = await fetch('/admin/productos/' + this.productId + '/imagenes/quitar', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
          this.images.splice(index, 1);
          this.updateMainPreview();
        } else {
          alert(data.message ?? 'Error al quitar la imagen.');
        }
      } catch (e) {
        alert('Error de red al quitar la imagen.');
      }
    },

    // ── update sidebar preview ──
    updateMainPreview() {
      const img  = document.getElementById('main-img-preview');
      const ph   = document.getElementById('main-preview-placeholder');
      if (this.images.length > 0) {
        if (img) { img.src = this.images[0].image_url; img.classList.remove('hidden'); }
        if (ph)  { ph.classList.add('hidden'); }
      } else {
        if (img) { img.src = ''; img.classList.add('hidden'); }
        if (ph)  { ph.classList.remove('hidden'); }
      }
    },
  };
}
</script>
