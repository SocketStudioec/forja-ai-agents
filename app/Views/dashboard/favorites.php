<?php
/** @var array $skills @var array $agents */
use App\Core\View;
?>
<div class="page-head">
  <div>
    <span class="eyebrow"><span class="dot"></span>Mi cuenta</span>
    <h1 style="margin-top:.9rem">Mi biblioteca</h1>
    <p>Los agentes y las habilidades que agregaste a tu cuenta. Descárgalos cuando quieras.</p>
  </div>
</div>

<?php if ($agents): ?>
  <h2 style="font-size:1.1rem;margin-bottom:1rem">Agentes</h2>
  <div class="grid mb-2">
    <?php foreach ($agents as $i => $a): ?>
      <?= View::partial('partials/agent-card', ['agent' => $a, 'delay' => min(4, $i + 1)]) ?>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if ($skills): ?>
  <h2 style="font-size:1.1rem;margin:2rem 0 1rem">Habilidades</h2>
  <div class="grid">
    <?php foreach ($skills as $i => $s): ?>
      <?= View::partial('partials/skill-card', ['skill' => $s, 'delay' => min(4, $i + 1)]) ?>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if (!$agents && !$skills): ?>
  <div class="shellbox">
    <div class="core empty">
      <span class="glyph"><?= icon('star', 22) ?></span>
      <h3>Tu biblioteca está vacía</h3>
      <p>Entra al catálogo y pulsa «Agregar a mi cuenta» en los agentes que vayas a usar.
         Quedan aquí para que los encuentres rápido y los descargues cuando quieras.</p>
      <a class="btn btn-primary btn-sm mt-1" href="<?= url('/agents') ?>">Ver el catálogo <?= btnIcon('arrow') ?></a>
    </div>
  </div>
<?php endif; ?>
