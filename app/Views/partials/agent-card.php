<?php
/**
 * Tarjeta de agente para la tienda.
 * @var array $agent  @var array $skillPreview  @var int $delay
 */
use App\Core\Str;
use App\Core\View;
use App\Core\Tier;
use App\Models\Agent;
use App\Models\Library;

$skillPreview = $skillPreview ?? ($agent['skill_preview'] ?? []);
$delay        = $delay ?? 0;
$compat       = Str::listFromCsv($agent['compatibility'] ?? '');
$count        = (int) ($agent['skills_count'] ?? count($skillPreview));
$enCuenta     = Library::hasAgent((int) $agent['id']);
$cats         = Agent::categoryPairs($agent);
$esDePago     = Tier::isPaid($agent);
?>
<article class="shellbox card agent-card reveal" <?= $delay ? 'data-d="' . (int) $delay . '"' : '' ?>>
  <div class="core">
    <div class="card-top">
      <span class="card-glyph" aria-hidden="true"><?= e(glyphFor((string) $agent['name'])) ?></span>
      <div style="min-width:0">
        <a class="card-title" href="<?= url('/agents/' . $agent['slug']) ?>"><?= e($agent['name']) ?></a>
        <?php if (!empty($agent['role_title'])): ?>
          <span class="card-role"><?= e($agent['role_title']) ?></span>
        <?php endif; ?>
      </div>
      <span class="badge push mono nowrap">v<?= e($agent['version']) ?></span>
    </div>

    <p class="card-desc"><?= e(Str::excerpt((string) $agent['short_description'], 130)) ?></p>

    <?php if ($skillPreview): ?>
      <div class="skill-strip">
        <?php foreach (array_slice($skillPreview, 0, 3) as $s): ?>
          <span class="skill-pill">
            <?= e(Str::excerpt((string) $s['name'], 22)) ?>
            <?php /* En una plantilla de pago no se insinúan los archivos: sólo
                     se nombra lo que sabe hacer. */ ?>
            <?php if (!$esDePago): ?><span class="ext">.json</span><?php endif; ?>
          </span>
        <?php endforeach; ?>
        <?php if ($count > 3): ?>
          <span class="skill-pill">+<?= $count - 3 ?></span>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="card-tags chips">
      <?php if ($esDePago): ?>
        <span class="badge badge-amber">De pago</span>
      <?php else: ?>
        <span class="badge badge-mint">AGENT.md</span>
      <?php endif; ?>
      <span class="badge badge-sky"><?= $count ?> skill<?= $count === 1 ? '' : 's' ?></span>
      <?php foreach (array_slice($cats, 0, 2) as $cat): ?>
        <a class="badge" href="<?= url('/agents') ?>?category=<?= e($cat['slug']) ?>"><?= e($cat['name']) ?></a>
      <?php endforeach; ?>
      <?php if (count($cats) > 2): ?><span class="badge">+<?= count($cats) - 2 ?></span><?php endif; ?>
    </div>

    <div class="card-meta">
      <?php if ($esDePago): ?>
        <span class="mono"><?= e(trim((string) ($agent['price_label'] ?? '')) ?: 'Consultar precio') ?></span>
      <?php else: ?>
        <span class="mono"><?= e(Str::compactNumber((int) $agent['downloads'])) ?> descargas</span>
      <?php endif; ?>
      <span class="sep" aria-hidden="true"></span>
      <span><?= e(Str::timeAgo($agent['updated_at'] ?? null)) ?></span>
      <a class="push" href="<?= url('/agents/' . $agent['slug']) ?>">Ver ficha</a>
    </div>

    <div class="card-actions">
      <?php if ($esDePago): ?>
        <a class="btn btn-primary btn-sm" href="<?= url('/agents/' . $agent['slug']) ?>">
          Ver qué hace <?= btnIcon('arrow') ?>
        </a>
        <span class="btn btn-ghost btn-sm" aria-disabled="true" title="Las plantillas de pago no se descargan">
          Sin descarga
        </span>
      <?php else: ?>
        <?= View::partial('partials/account-button', [
            'type'   => 'agent',
            'id'     => (int) $agent['id'],
            'active' => $enCuenta,
            'name'   => (string) $agent['name'],
        ]) ?>
        <a class="btn btn-ghost btn-sm" href="<?= url('/agents/' . $agent['slug'] . '/download') ?>?format=zip">
          Descargar <?= btnIcon('download') ?>
        </a>
      <?php endif; ?>
    </div>
  </div>
</article>
