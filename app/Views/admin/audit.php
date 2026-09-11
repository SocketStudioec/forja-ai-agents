<?php
/** @var array $items @var array $actions @var string $action @var int $page @var int $pages @var int $total */
use App\Core\Audit;
use App\Core\Str;
use App\Core\View;
?>
<div class="page-head">
  <div>
    <span class="eyebrow"><span class="dot"></span>Trazabilidad</span>
    <h1 style="margin-top:.9rem">Auditoría</h1>
    <p><?= (int) $total ?> evento(s). Nunca se registran contraseñas ni claves.</p>
  </div>
</div>

<form class="toolbar mb-2" method="get" action="<?= url('/admin/audit') ?>">
  <label class="filter-select" style="flex:1">
    <span class="sr-only">Acción</span>
    <select class="select" name="action" data-autosubmit>
      <option value="">Todas las acciones</option>
      <?php foreach ($actions as $a): ?>
        <option value="<?= e($a['action']) ?>" <?= $action === $a['action'] ? 'selected' : '' ?>>
          <?= e(Audit::label((string) $a['action'])) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>
  <button class="btn btn-primary btn-sm" type="submit">Filtrar <?= btnIcon('filter') ?></button>
</form>

<div class="shellbox">
  <div class="core">
    <?php if ($items): ?>
      <div class="table-wrap">
        <table class="data">
          <thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Recurso</th><th>Detalle</th></tr></thead>
          <tbody>
            <?php foreach ($items as $l):
                $meta = $l['metadata'] ? json_decode((string) $l['metadata'], true) : null; ?>
              <tr>
                <td class="text-xs muted nowrap"><?= e(date('d/m/Y H:i', strtotime((string) $l['created_at']))) ?></td>
                <td class="text-xs"><?= e($l['username'] ?: ($l['actor_label'] ?: 'visitante')) ?></td>
                <td><span class="badge"><?= e(Audit::label((string) $l['action'])) ?></span></td>
                <td class="text-xs mono">
                  <?= e($l['resource_type']) ?><?= $l['resource_id'] ? '#' . (int) $l['resource_id'] : '' ?>
                </td>
                <td class="text-xs muted">
                  <?php if (is_array($meta)):
                      $parts = [];
                      foreach ($meta as $k => $v) { $parts[] = $k . ': ' . (is_scalar($v) ? (string) $v : '—'); }
                      echo e(Str::excerpt(implode(' · ', $parts), 90));
                  else: ?>—<?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty">
        <span class="glyph"><?= icon('shield', 22) ?></span>
        <h3>Sin eventos registrados</h3>
        <p>Las acciones sobre usuarios, contenido y descargas aparecerán aquí.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<?= View::partial('partials/pager', ['page' => $page, 'pages' => $pages, 'path' => '/admin/audit']) ?>
