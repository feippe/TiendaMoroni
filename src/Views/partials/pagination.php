<?php if ($pagination['total_pages'] <= 1) return; ?>

<?php
// Build current query params without 'page' key
$params = $_GET;
unset($params['page']);
$base = '?' . http_build_query($params) . '&page=';
?>

<nav class="flex items-center justify-center gap-1 mt-10" aria-label="Paginación">
  <?php if ($pagination['has_prev']): ?>
  <a href="<?= $base . ($pagination['page'] - 1) ?>"
     class="px-3 py-2 text-sm rounded-lg border border-warm-300 text-warm-700 hover:bg-warm-100 transition">
    ← Anterior
  </a>
  <?php endif; ?>

  <?php for ($i = max(1, $pagination['page'] - 2); $i <= min($pagination['total_pages'], $pagination['page'] + 2); $i++): ?>
  <a href="<?= $base . $i ?>"
     class="px-3 py-2 text-sm rounded-lg border transition
            <?= $i === $pagination['page']
                ? 'bg-navy border-navy text-white font-semibold'
                : 'border-warm-300 text-warm-700 hover:bg-warm-100' ?>">
    <?= $i ?>
  </a>
  <?php endfor; ?>

  <?php if ($pagination['has_next']): ?>
  <a href="<?= $base . ($pagination['page'] + 1) ?>"
     class="px-3 py-2 text-sm rounded-lg border border-warm-300 text-warm-700 hover:bg-warm-100 transition">
    Siguiente →
  </a>
  <?php endif; ?>

  <span class="ml-4 text-xs text-warm-500">
    <?= $pagination['total'] ?> resultados
  </span>
</nav>
