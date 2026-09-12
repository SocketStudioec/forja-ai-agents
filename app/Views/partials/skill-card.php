<?php
/** @var array $skill @var int $delay */
use App\Core\Str;
use App\Core\Tier;
use App\Core\View;
use App\Models\Library;

$delay    = $delay ?? 0;
$compat   = Str::listFromCsv($skill['compatibility'] ?? '');
$tags     = Str::listFromCsv($skill['tags'] ?? '');
$enCuenta = Library::hasSkill((int) $skill['id']);
$esDePago = Tier::isPaid($skill);
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
      <?php if ($esDePago): ?>
        <span class="badge badge-amber">De pago</span>
      <?php else: ?>
        <span class="badge badge-sky">.json</span>
        <span class="badge badge-mint">.md</span>
      <?php endif; ?>
      <?php if (!empty($skill['category_name'])): ?>
        <span class="badge"><?= e($skill['category_name']) ?></span>
      <?php elseif ($compat): ?>
        <span class="badge"><?= e($compat[0]) ?></span>
      <?php endif; ?>
      <?php if ($tags): ?><span class="badge">#<?= e($tags[0]) ?></span><?php endif; ?>
    </div>

    <div class="card-meta">
      <?php if ($esDePago): ?>
        <span class="mono"><?= e(trim((string) ($skill['price_label'] ?? '')) ?: 'Consultar precio') ?></span>
      <?php else: ?>
        <span class="mono"><?= e(Str::compactNumber((int) $skill['downloads'])) ?> descargas</span>
      <?php endif; ?>
      <span class="sep" aria-hidden="true"></span>
      <span><?= e(Str::timeAgo($skill['updated_at'] ?? null)) ?></span>
      <a class="push" href="<?= url('/skills/' . $skill['slug']) ?>">Ver ficha</a>
    </div>

    <div class="card-actions">
      <?php if ($esDePago): ?>
        <a class="btn btn-primary btn-sm" href="<?= url('/skills/' . $skill['slug']) ?>">
          Ver qué hace <?= btnIcon('arrow') ?>
        </a>
        <span class="btn btn-ghost btn-sm" aria-disabled="true" title="Las plantillas de pago no se descargan">
          Sin descarga
        </span>
      <?php else: ?>
        <?= View::partial('partials/account-button', [
            'type'   => 'skill',
            'id'     => (int) $skill['id'],
            'active' => $enCuenta,
            'name'   => (string) $skill['name'],
        ]) ?>
        <a class="btn btn-ghost btn-sm" href="<?= url('/skills/' . $skill['slug'] . '/download') ?>?format=json">
          Descargar <?= btnIcon('download') ?>
        </a>
      <?php endif; ?>
    </div>
  </div>
</article>
