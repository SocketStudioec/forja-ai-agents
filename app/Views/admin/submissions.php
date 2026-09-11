<?php
/** @var array $result @var array $filters */
use App\Core\Str;
use App\Core\View;
use App\Models\Submission;

$statuses = ['pending', 'under_review', 'approved', 'rejected', 'published', 'archived'];
?>
<div class="page-head">
  <div>
    <span class="eyebrow"><span class="dot"></span>Revisión</span>
    <h1 style="margin-top:.9rem">Bandeja de envíos</h1>
    <p>Nada de lo que llega por aquí se publica sin que alguien lo apruebe.</p>
  </div>
</div>

<form class="toolbar mb-2" method="get" action="<?= url('/admin/submissions') ?>">
  <label class="search">
    <span class="sr-only">Buscar envío</span>
    <?= icon('search') ?>
    <input class="input" type="search" name="q" value="<?= e($filters['q']) ?>"
           placeholder="Nombre, correo o identificador…">
  </label>
  <label class="filter-select">
    <span class="sr-only">Estado</span>
    <select class="select" name="status" data-autosubmit>
      <option value="">Todos los estados</option>
      <?php foreach ($statuses as $s): ?>
        <option value="<?= e($s) ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>>
          <?= e(Submission::statusLabel($s)) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>
  <label class="filter-select">
    <span class="sr-only">Tipo</span>
    <select class="select" name="kind" data-autosubmit>
      <option value="">Skills y agentes</option>
      <option value="skill" <?= $filters['kind'] === 'skill' ? 'selected' : '' ?>>Sólo skills</option>
      <option value="agent" <?= $filters['kind'] === 'agent' ? 'selected' : '' ?>>Sólo agentes</option>
    </select>
  </label>
  <button class="btn btn-primary btn-sm" type="submit">Filtrar <?= btnIcon('filter') ?></button>
</form>

<div class="shellbox">
  <div class="core">
    <?php if ($result['items']): ?>
      <div class="table-wrap">
        <table class="data">
          <thead>
            <tr><th>Skill</th><th>Autor</th><th>Email</th><th>Categoría</th><th>Fecha</th><th>Estado</th><th>Acción</th></tr>
          </thead>
          <tbody>
            <?php foreach ($result['items'] as $s):
                $tone = Submission::statusTone((string) $s['status']); ?>
              <tr>
                <td>
                  <a class="row-main" href="<?= url('/admin/submissions/' . $s['id']) ?>"><?= e($s['skill_name']) ?></a>
                  <span class="row-sub mono">
                    <?= e($s['reference']) ?>
                    · <?= $s['kind'] === 'agent' ? 'agente' : 'skill' ?>
                    <?php if (!empty($s['file_name'])): ?> · <?= e($s['file_name']) ?><?php endif; ?>
                  </span>
                </td>
                <td class="text-xs"><?= e($s['name'] ?: '—') ?></td>
                <td class="text-xs mono"><?= e($s['email']) ?></td>
                <td class="text-xs"><?= e($s['category_name'] ?: '—') ?></td>
                <td class="text-xs muted nowrap"><?= e(Str::timeAgo((string) $s['created_at'])) ?></td>
                <td><span class="badge <?= $tone ? 'badge-' . e($tone) : '' ?>">
                  <?= e(Submission::statusLabel((string) $s['status'])) ?></span></td>
                <td class="row-actions">
                  <a class="btn <?= in_array($s['status'], ['pending', 'under_review'], true) ? 'btn-primary' : 'btn-ghost' ?> btn-sm"
                     href="<?= url('/admin/submissions/' . $s['id']) ?>">Revisar</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty">
        <span class="glyph"><?= icon('inbox', 22) ?></span>
        <h3>La bandeja está vacía</h3>
        <p>Cuando alguien envíe una skill aparecerá aquí y recibirás un correo.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<?= View::partial('partials/pager', ['page' => $result['page'], 'pages' => $result['pages'], 'path' => '/admin/submissions']) ?>
