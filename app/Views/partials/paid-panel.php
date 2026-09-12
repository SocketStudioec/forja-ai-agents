<?php
/**
 * Panel que sustituye a la descarga en una plantilla de pago.
 *
 * @var array  $row   agente o habilidad
 * @var string $tipo  'agente' | 'habilidad'
 */
use App\Core\Tier;

$precio  = trim((string) ($row['price_label'] ?? ''));
$destino = Tier::contactUrl($row);
$externo = strncmp($destino, 'http', 4) === 0;
?>
<div class="shellbox tight">
  <div class="core pad">
    <div class="row-tight" style="justify-content:space-between;gap:.6rem">
      <h3 style="font-size:.95rem">Plantilla de pago</h3>
      <span class="badge badge-amber">De pago</span>
    </div>

    <?php if ($precio !== ''): ?>
      <p class="mono" style="font-size:1.35rem;letter-spacing:-.03em;margin:.9rem 0 .2rem;color:var(--ink)">
        <?= e($precio) ?>
      </p>
    <?php endif; ?>

    <p class="text-sm muted mt-1">
      Este <?= e($tipo) ?> no se descarga desde el catálogo. Aquí puedes ver qué hace
      y qué incluye; el contenido se entrega al contratarlo.
    </p>

    <a class="btn btn-primary btn-block mt-2" href="<?= e($destino) ?>"
       <?= $externo ? 'target="_blank" rel="noopener"' : '' ?>>
      Quiero esta plantilla <?= btnIcon('arrow-up-right') ?>
    </a>

    <div class="meta-list mt-2">
      <div><span class="k">Reglas</span><span class="v">Se entregan al contratar</span></div>
      <div><span class="k">Descarga</span><span class="v">No disponible</span></div>
    </div>
  </div>
</div>
