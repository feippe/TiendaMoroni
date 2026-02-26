<?php $layout = 'layout/admin'; ?>

<div class="mb-6">
  <h2 class="text-xl font-bold text-warm-900">Preguntas y respuestas</h2>
  <p class="text-sm text-warm-500 mt-1"><?= count(array_filter($questions, fn($q) => !$q['answer'])) ?> sin responder</p>
</div>

<?php if (empty($questions)): ?>
<div class="bg-white rounded-2xl border border-warm-200 p-10 text-center text-warm-400">
  No hay preguntas aún.
</div>
<?php else: ?>
<div class="space-y-4">
  <?php foreach ($questions as $q): ?>
  <div class="bg-white rounded-2xl border <?= $q['answer'] ? 'border-warm-200' : 'border-yellow-300' ?> p-5">
    <div class="flex justify-between items-start mb-2">
      <div>
        <span class="text-xs text-warm-400"><?= date('d/m/Y H:i', strtotime($q['created_at'])) ?></span>
        <span class="mx-2 text-warm-300">·</span>
        <a href="/producto/<?= e($q['product_slug']) ?>" target="_blank"
           class="text-xs text-brand-600 hover:underline"><?= e($q['product_name']) ?></a>
        <span class="mx-2 text-warm-300">·</span>
        <span class="text-xs text-warm-500"><?= e($q['user_name'] ?? 'Anónimo') ?></span>
      </div>
      <form method="post" action="/admin/preguntas/<?= (int)$q['id'] ?>/eliminar"
            onsubmit="return confirm('¿Eliminar esta pregunta?')">
        <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
        <button type="submit" class="text-xs text-red-400 hover:text-red-600 transition">Eliminar</button>
      </form>
    </div>

    <p class="text-warm-800 font-medium mb-3">❓ <?= e($q['question']) ?></p>

    <?php if ($q['answer']): ?>
    <div class="bg-green-50 rounded-xl px-4 py-3 text-sm text-green-800">
      ✅ <?= e($q['answer']) ?>
      <span class="block text-xs text-green-500 mt-1"><?= date('d/m/Y', strtotime($q['answered_at'])) ?></span>
    </div>
    <?php else: ?>
    <form method="post" action="/admin/preguntas/<?= (int)$q['id'] ?>/responder" class="flex gap-2 mt-2">
      <input type="hidden" name="_csrf" value="<?= csrfToken() ?>">
      <textarea name="answer" rows="2" required placeholder="Escribir respuesta…"
                class="flex-1 border border-warm-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 resize-none transition"></textarea>
      <button type="submit"
              class="self-end bg-brand-700 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-brand-800 transition whitespace-nowrap">
        Responder
      </button>
    </form>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
