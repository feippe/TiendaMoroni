/**
 * TiendaMoroni Admin — admin.js
 * Admin panel specific client-side utilities.
 */

/* ─────────────────────────────────────────────────────────────────────────
 * Slug auto-generation from name inputs
 * Any input with data-source="name" will write a slug into
 * the sibling/nearby input with data-target="slug".
 * Alpine-based forms also handle this inline (see product/category forms).
 * ───────────────────────────────────────────────────────────────────────── */
function toSlug(str) {
  return str
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')   // strip diacritics
    .replace(/[^a-z0-9\s-]/g, '')
    .replace(/[\s-]+/g, '-')
    .replace(/^-+|-+$/g, '');
}

document.addEventListener('DOMContentLoaded', () => {
  const nameInputs = document.querySelectorAll('[data-slug-source]');

  nameInputs.forEach(nameEl => {
    const targetId  = nameEl.dataset.slugSource;
    const slugEl    = document.getElementById(targetId);
    if (!slugEl) return;

    let touched = slugEl.value !== '';   // if slug already filled, don't overwrite

    slugEl.addEventListener('input', () => { touched = true; });

    nameEl.addEventListener('input', () => {
      if (!touched) {
        slugEl.value = toSlug(nameEl.value);
      }
    });
  });
});

/* ─────────────────────────────────────────────────────────────────────────
 * Image preview before upload
 * ───────────────────────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  const fileInputs = document.querySelectorAll('input[type="file"][data-preview]');

  fileInputs.forEach(input => {
    const previewId = input.dataset.preview;
    const preview   = document.getElementById(previewId);
    if (!preview) return;

    input.addEventListener('change', () => {
      const file = input.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = e => { preview.src = e.target.result; preview.classList.remove('hidden'); };
      reader.readAsDataURL(file);
    });
  });
});

/* ─────────────────────────────────────────────────────────────────────────
 * Confirm delete for any form with data-confirm attribute
 * ───────────────────────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('form[data-confirm]').forEach(form => {
    form.addEventListener('submit', e => {
      if (!confirm(form.dataset.confirm)) {
        e.preventDefault();
      }
    });
  });
});

/* ─────────────────────────────────────────────────────────────────────────
 * Sidebar toggle helper (supplements Alpine in the admin layout)
 * ───────────────────────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  const toggle  = document.getElementById('sidebar-toggle');
  const sidebar = document.getElementById('admin-sidebar');
  const overlay = document.getElementById('sidebar-overlay');
  if (!toggle || !sidebar) return;

  function open()  { sidebar.classList.remove('-translate-x-full'); overlay?.classList.remove('hidden'); }
  function close() { sidebar.classList.add('-translate-x-full');    overlay?.classList.add('hidden'); }

  toggle.addEventListener('click', () => {
    sidebar.classList.contains('-translate-x-full') ? open() : close();
  });
  overlay?.addEventListener('click', close);
});
