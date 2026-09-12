<?php
/** @var array $result @var array $filters @var array $categories */
use App\Core\Csrf;
use App\Core\Str;
use App\Core\View;
use App\Core\Tier;
use App\Core\Visibility;
use App\Models\Skill;

$statuses = ['draft', 'pending', 'under_review', 'approved', 'rejected', 'published', 'archived'];
?>
<div class="page-head">
  <div>
    <span class="eyebrow"><span class="dot"></span>Administración</span>
    <h1 style="margin-top:.9rem">Todos los agentes</h1>
    <p><?= (int) $result['total'] ?> agente<?= $result['total'] === 1 ? '' : 's' ?> en el catálogo.</p>
  </div>
</div>

<form class="toolbar mb-2" method="get" action="<?= url('/admin/agents') ?>">
  <label class="search">
    <span class="sr-only">Buscar</span>
    <?= icon('search') ?>
    <input class="input" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Nombre, slug o correo…">
  </label>
  <label class="filter-select">
    <span class="sr-only">Estado</span>
    <select class="select" name="status" data-autosubmit>
      <option value="">Todos los estados</option>
      <?php foreach ($statuses as $s): ?>
        <option value="<?= e($s) ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= e(Skill::statusLabel($s)) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label class="filter-select">
    <span class="sr-only">Categoría</span>
    <select class="select" name="category" data-autosubmit>
      <option value="">Todas las categorías</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= e($c['slug']) ?>" <?= $filters['category'] === $c['slug'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label class="filter-select">
    <span class="sr-only">Tipo</span>
    <select class="select" name="tier" data-autosubmit>
      <option value="">Gratis y de pago</option>
      <option value="free" <?= ($filters['tier'] ?? '') === 'free' ? 'selected' : '' ?>>Gratuitos</option>
      <option value="paid" <?= ($filters['tier'] ?? '') === 'paid' ? 'selected' : '' ?>>De pago</option>
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
            <tr><th>Agente</th><th>Autor</th><th>Skills</th><th>Estado</th><th>Visibilidad</th><th class="num">Descargas</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($result['items'] as $a): ?>
              <tr>
                <td>
                  <a class="row-main" href="<?= url('/agents/' . $a['slug']) ?>"><?= e($a['name']) ?></a>
                  <span class="row-sub mono">
                    v<?= e($a['version']) ?>
                    <?php if (Tier::isPaid($a)): ?>
                      <span class="badge badge-amber">de pago</span>
                    <?php endif; ?>
                  </span>
                </td>
                <td class="text-xs">
                  <?= e($a['author_display']) ?>
                  <span class="row-sub"><?= e($a['author_email'] ?: ($a['author_username'] ?: '—')) ?></span>
                </td>
                <td class="mono"><?= (int) $a['skills_count'] ?></td>
                <td>
                  <form method="post" action="<?= url('/admin/agents/' . $a['id'] . '/status') ?>" class="row-tight">
                    <?= Csrf::field() ?>
                    <select class="select" name="status" data-autosubmit style="padding:.4rem .6rem;font-size:.78rem;min-width:120px">
                      <?php foreach ($statuses as $st): ?>
                        <option value="<?= e($st) ?>" <?= $a['status'] === $st ? 'selected' : '' ?>>
                          <?= e(Skill::statusLabel($st)) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <noscript><button class="btn btn-ghost btn-sm" type="submit">Aplicar</button></noscript>
                  </form>
                </td>
                <td>
                  <form method="post" action="<?= url('/admin/agents/' . $a['id'] . '/status') ?>">
                    <?= Csrf::field() ?>
                    <select class="select" name="visibility" data-autosubmit
                            style="padding:.4rem .6rem;font-size:.78rem;min-width:118px"
                            aria-label="Visibilidad de <?= e($a['name']) ?>">
                      <?php foreach (Visibility::all() as $vv): ?>
                        <option value="<?= e($vv) ?>" <?= $a['visibility'] === $vv ? 'selected' : '' ?>>
                          <?= e(Visibility::label($vv)) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <noscript><button class="btn btn-ghost btn-sm" type="submit">Aplicar</button></noscript>
                  </form>
                </td>
                <td class="num"><?= e(Str::compactNumber((int) $a['downloads'])) ?></td>
                <td>
                  <div class="row-actions">
                    <a class="btn btn-ghost btn-sm" href="<?= url('/dashboard/agents/' . $a['id'] . '/edit') ?>">Editar</a>
                    <form method="post" action="<?= url('/admin/agents/' . $a['id'] . '/delete') ?>"
                          data-confirm="Se elimina «<?= e($a['name']) ?>». ¿Continuar?">
                      <?= Csrf::field() ?>
                      <button class="icon-btn" type="submit" aria-label="Eliminar"><?= icon('trash', 15) ?></button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty">
        <span class="glyph"><?= icon('agent', 22) ?></span>
        <h3>Ningún agente coincide</h3>
        <p>Prueba a limpiar los filtros.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<?= View::partial('partials/pager', ['page' => $result['page'], 'pages' => $result['pages'], 'path' => '/admin/agents']) ?>
