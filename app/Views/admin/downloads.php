<?php
/** @var array $byFormat @var array $byDay @var array $recent @var int $total */
use App\Core\Str;

$max = 1;
foreach ($byDay as $d) {
    $max = max($max, (int) $d['n']);
}
?>
<div class="page-head">
  <div>
    <span class="eyebrow"><span class="dot"></span>Uso</span>
    <h1 style="margin-top:.9rem">Descargas</h1>
    <p><?= e(Str::compactNumber($total)) ?> descargas registradas desde el inicio.</p>
  </div>
</div>

<div class="bento">
  <!-- --------------------------------------------------- Últimos 30 días -->
  <div class="shellbox w8">
    <div class="core pad-lg">
      <h2 style="font-size:1.05rem">Últimos 30 días</h2>

      <?php if ($byDay): ?>
        <div style="display:flex;align-items:flex-end;gap:3px;height:150px;margin-top:1.6rem">
          <?php foreach ($byDay as $d):
              $h = max(3, (int) round(((int) $d['n'] / $max) * 100)); ?>
            <span style="flex:1;min-width:3px;height:<?= $h ?>%;border-radius:3px 3px 0 0;background:var(--mint);opacity:.75"
                  title="<?= e(date('d/m', strtotime((string) $d['d']))) ?>: <?= (int) $d['n'] ?>"></span>
          <?php endforeach; ?>
        </div>
        <div class="row text-xs faint" style="margin-top:.6rem">
          <span><?= e(date('d/m', strtotime((string) $byDay[0]['d']))) ?></span>
          <span class="push"><?= e(date('d/m', strtotime((string) $byDay[count($byDay) - 1]['d']))) ?></span>
        </div>
      <?php else: ?>
        <p class="text-sm muted mt-2">Todavía no hay descargas en este periodo.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- ------------------------------------------------------- Por formato -->
  <div class="shellbox">
    <div class="core pad-lg">
      <h2 style="font-size:1.05rem">Por formato</h2>
      <?php if ($byFormat): ?>
        <div class="meta-list mt-2">
          <?php foreach ($byFormat as $f): ?>
            <div>
              <span class="k mono">.<?= e($f['format']) ?></span>
              <span class="v"><?= e(Str::compactNumber((int) $f['n'])) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="text-sm muted mt-2">Sin datos.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="shellbox mt-3">
  <div class="core">
    <div class="pad" style="border-bottom:1px solid var(--line)">
      <h2 style="font-size:1.05rem">Últimas descargas</h2>
    </div>

    <?php if ($recent): ?>
      <div class="table-wrap">
        <table class="data">
          <thead><tr><th>Recurso</th><th>Tipo</th><th>Formato</th><th>Usuario</th><th>Fecha</th></tr></thead>
          <tbody>
            <?php foreach ($recent as $d): ?>
              <tr>
                <td>
                  <?php if (!empty($d['agent_name'])): ?>
                    <a class="row-main" href="<?= url('/agents/' . $d['agent_slug']) ?>"><?= e($d['agent_name']) ?></a>
                  <?php elseif (!empty($d['skill_name'])): ?>
                    <a class="row-main" href="<?= url('/skills/' . $d['skill_slug']) ?>"><?= e($d['skill_name']) ?></a>
                  <?php else: ?>
                    <span class="muted">Recurso eliminado</span>
                  <?php endif; ?>
                </td>
                <td><span class="badge <?= $d['kind'] === 'agent' ? 'badge-mint' : ($d['kind'] === 'pack' ? 'badge-amber' : 'badge-sky') ?>">
                  <?= e($d['kind']) ?></span></td>
                <td class="mono text-xs">.<?= e($d['format']) ?></td>
                <td class="text-xs"><?= e($d['username'] ?: 'visitante') ?></td>
                <td class="text-xs muted nowrap"><?= e(Str::timeAgo((string) $d['created_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty">
        <span class="glyph"><?= icon('download', 22) ?></span>
        <h3>Sin descargas todavía</h3>
        <p>En cuanto alguien baje una skill o un agente aparecerá aquí.</p>
      </div>
    <?php endif; ?>
  </div>
</div>
