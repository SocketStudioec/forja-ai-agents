<?php
/** @var array $skill @var int $delay */
use App\Core\Str;

$delay  = $delay ?? 0;
$compat = Str::listFromCsv($skill['compatibility'] ?? '');
$tags   = Str::listFromCsv($skill['tags'] ?? '');
?>
<article class="shellbox card reveal" <?= $delay ? 'data-d="' . (int) $delay . '"' : '' ?>>
  <div class="core">
    <div class="card-top">
      <span class="card-glyph sky" aria-hidden="true"><?= icon('skill', 18) ?></span>
      <div style="min-width:0">
        <a class="card-title" href="<?= url('/skills/' . $skill['slug']) ?>"><?= e($skill['name']) ?></a>
        <span class="card-role"><?= e($skill['author_display'] ?? 'Anónimo') ?></span>
      </div>
      <span class="badge push mono nowrap">v<?= e($skill['version']) ?></span>
    </div>

    <p class="card-desc"><?= e(Str::excerpt((string) $skill['short_description'], 130)) ?></p>

    <div class="card-tags chips">
      <span class="badge badge-sky">.json</span>
      <span class="badge badge-mint">.md</span>
      <?php if (!empty($skill['category_name'])): ?>
        <span class="badge"><?= e($skill['category_name']) ?></span>
      <?php elseif ($compat): ?>
        <span class="badge"><?= e($compat[0]) ?></span>
      <?php endif; ?>
      <?php if ($tags): ?><span class="badge">#<?= e($tags[0]) ?></span><?php endif; ?>
    </div>

    <div class="card-foot">
      <span class="mono"><?= e(Str::compactNumber((int) $skill['downloads'])) ?> descargas</span>
      <span class="sep" aria-hidden="true"></span>
      <span class="hide-sm"><?= e(Str::timeAgo($skill['updated_at'] ?? null)) ?></span>
      <span class="actions">
        <button type="button" class="icon-btn" data-cart-toggle="skill" data-slug="<?= e($skill['slug']) ?>"
                aria-pressed="false" title="Añadir a mi paquete" aria-label="Añadir <?= e($skill['name']) ?> a mi paquete">
          <?= icon('cart', 15) ?>
        </button>
        <a class="btn btn-sm btn-ghost" href="<?= url('/skills/' . $skill['slug'] . '/download') ?>?format=json">
          JSON <?= btnIcon('download') ?>
        </a>
      </span>
    </div>
  </div>
</article>
