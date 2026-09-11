<?php
/** @var int $page @var int $pages @var string $path */
if (($pages ?? 1) <= 1) {
    return;
}
$window = 2;
$from   = max(1, $page - $window);
$to     = min($pages, $page + $window);
?>
<nav class="pager" aria-label="Paginación">
  <?php if ($page > 1): ?>
    <a href="<?= e(queryUrl($path, ['page' => $page - 1])) ?>" rel="prev" aria-label="Página anterior">←</a>
  <?php endif; ?>

  <?php if ($from > 1): ?>
    <a href="<?= e(queryUrl($path, ['page' => 1])) ?>">1</a>
    <?php if ($from > 2): ?><span class="is-dots">…</span><?php endif; ?>
  <?php endif; ?>

  <?php for ($i = $from; $i <= $to; $i++): ?>
    <?php if ($i === $page): ?>
      <span class="is-current" aria-current="page"><?= $i ?></span>
    <?php else: ?>
      <a href="<?= e(queryUrl($path, ['page' => $i])) ?>"><?= $i ?></a>
    <?php endif; ?>
  <?php endfor; ?>

  <?php if ($to < $pages): ?>
    <?php if ($to < $pages - 1): ?><span class="is-dots">…</span><?php endif; ?>
    <a href="<?= e(queryUrl($path, ['page' => $pages])) ?>"><?= $pages ?></a>
  <?php endif; ?>

  <?php if ($page < $pages): ?>
    <a href="<?= e(queryUrl($path, ['page' => $page + 1])) ?>" rel="next" aria-label="Página siguiente">→</a>
  <?php endif; ?>
</nav>
