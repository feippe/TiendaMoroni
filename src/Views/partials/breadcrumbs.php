<!-- Breadcrumbs with JSON-LD -->
<?php
// $crumbs expected: [ ['name'=>'...', 'url'=>'...'], ... ]
?>
<nav class="text-xs text-warm-500" aria-label="Breadcrumb">
  <ol class="flex items-center flex-wrap gap-1">
    <?php foreach ($crumbs as $i => $crumb): ?>
    <?php $isLast = ($i === count($crumbs) - 1); ?>
    <li class="flex items-center gap-1">
      <?php if ($isLast): ?>
      <span class="text-warm-700 font-medium"><?= e($crumb['name']) ?></span>
      <?php else: ?>
      <a href="<?= e($crumb['url']) ?>" class="hover:text-brand-800 transition"><?= e($crumb['name']) ?></a>
      <svg class="w-3 h-3 text-warm-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
      </svg>
      <?php endif; ?>
    </li>
    <?php endforeach; ?>
  </ol>
</nav>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    <?php foreach ($crumbs as $i => $crumb): ?>
    {
      "@type": "ListItem",
      "position": <?= $i + 1 ?>,
      "name": "<?= addslashes($crumb['name']) ?>",
      "item": "<?= e($crumb['url']) ?>"
    }<?= $i < count($crumbs) - 1 ? ',' : '' ?>
    <?php endforeach; ?>
  ]
}
</script>
