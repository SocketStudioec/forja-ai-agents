<?php
/** @var array $result @var array $filters @var array $categories @var array $compat */
use App\Core\Csrf;
use App\Core\Str;
use App\Core\View;
use App\Core\Visibility;
use App\Models\Skill;

$statuses = ['draft', 'pending', 'under_review', 'approved', 'rejected', 'published', 'archived'];
?>
<div class="page-head">
  <div>
    <span class="eyebrow"><span class="dot"></span>Administración</span>
    <h1 style="margin-top:.9rem">Todas las skills</h1>
    <p><?= (int) $result['total'] ?> habilidad<?= $result['total'] === 1 ? '' : 'es' ?> de todos los usuarios.</p>
  </div>
</div>

<form class="toolbar mb-2" method="get" action="<?= url('/admin/skills') ?>">
  <label class="search">
    <span class="sr-only">Buscar</span>
    <?= icon('search') ?>
    <input class="input" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Nombre, slug o correo del autor…">
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
    <span class="sr-only">Visibilidad</span>
    <select class="select" name="visibility" data-autosubmit>
      <option value="">Toda visibilidad</option>
      <option value="public"   <?= $filters['visibility'] === 'public' ? 'selected' : '' ?>>Pública</option>
      <option value="unlisted" <?= $filters['visibility'] === 'unlisted' ? 'selected' : '' ?>>Con enlace</option>
      <option value="private"  <?= $filters['visibility'] === 'private' ? 'selected' : '' ?>>Privada</option>
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
    <span class="sr-only">Compatibilidad</span>
    <select class="select" name="compat" data-autosubmit>
      <option value="">Cualquier agente</option>
      <?php foreach ($compat as $c): ?>
        <option value="<?= e($c) ?>" <?= $filters['compat'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
      <?php endforeach; ?>
    </select>
  </label>

  <button class="btn btn-primary btn-sm" type="submit">Filtrar <?= btnIcon('filter') ?></button>
</form>

<div class="stack">
  <?php if ($result['items']): ?>
    <?php foreach ($result['items'] as $s): ?>
      <div class="shellbox tight">
        <div class="core pad">
          <div class="row" style="align-items:flex-start">
            <div style="min-width:0;flex:1">
              <div class="row-tight" style="gap:.5rem;flex-wrap:wrap">
                <a class="row-main" style="font-size:.98rem;font-weight:600"
                   href="<?= url('/skills/' . $s['slug']) ?>"><?= e($s['name']) ?></a>
                <span class="badge mono">v<?= e($s['version']) ?></span>
                <span class="badge <?= $s['status'] === 'published' ? 'badge-mint' : ($s['status'] === 'rejected' ? 'badge-danger' : ($s['status'] === 'pending' ? 'badge-amber' : '')) ?>">
                  <?= e(Skill::statusLabel((string) $s['status'])) ?>
                </span>
                <span class="badge badge-<?= e(Visibility::tone((string) $s['visibility'])) ?>"
                      title="<?= e(Visibility::hint((string) $s['visibility'])) ?>">
                  <?= e(Visibility::label((string) $s['visibility'])) ?>
                </span>
                <?php if ((int) $s['featured'] === 1): ?><span class="badge badge-amber">destacada</span><?php endif; ?>
              </div>

              <p class="text-sm muted mt-1" style="margin-bottom:.5rem">
                <?= e(Str::excerpt((string) $s['short_description'], 140)) ?>
              </p>

              <div class="row-tight text-xs faint" style="gap:.8rem;flex-wrap:wrap">
                <span><?= icon('users', 12) ?> <?= e($s['author_display']) ?></span>
                <?php if (!empty($s['author_email']) || !empty($s['author_username'])): ?>
                  <span class="mono"><?= e($s['author_email'] ?: $s['author_username']) ?></span>
                <?php endif; ?>
                <span><?= e($s['category_name'] ?: 'Sin categoría') ?></span>
                <span class="mono"><?= (int) $s['downloads'] ?> descargas</span>
                <span><?= e(Str::timeAgo((string) $s['updated_at'])) ?></span>
              </div>
            </div>

            <div class="row-actions" style="flex:none">
              <?= View::partial('partials/visibility-toggle', [
                  'action'  => url('/admin/skills/' . $s['id'] . '/status'),
                  'current' => (string) $s['visibility'],
                  'label'   => (string) $s['name'],
                  'compact' => true,
              ]) ?>
              <a class="btn btn-ghost btn-sm" href="<?= url('/dashboard/skills/' . $s['id'] . '/edit') ?>">Editar</a>
              <form method="post" action="<?= url('/admin/skills/' . $s['id'] . '/feature') ?>">
                <?= Csrf::field() ?>
                <button class="icon-btn <?= (int) $s['featured'] === 1 ? 'is-on' : '' ?>" type="submit"
                        aria-label="Destacar"><?= icon('star', 15) ?></button>
              </form>
              <form method="post" action="<?= url('/admin/skills/' . $s['id'] . '/delete') ?>"
                    data-confirm="Se elimina «<?= e($s['name']) ?>» de forma permanente. ¿Continuar?">
                <?= Csrf::field() ?>
                <button class="icon-btn" type="submit" aria-label="Eliminar"><?= icon('trash', 15) ?></button>
              </form>
            </div>
          </div>

          <details style="margin-top:.9rem">
            <summary class="text-sm" style="cursor:pointer;color:var(--ink-mute)">Cambiar estado, visibilidad o dejar una nota</summary>
            <form method="post" action="<?= url('/admin/skills/' . $s['id'] . '/status') ?>" class="mt-2">
              <?= Csrf::field() ?>
              <div class="form-grid">
                <div class="field">
                  <label class="label" for="st-<?= (int) $s['id'] ?>">Nuevo estado</label>
                  <select class="select" id="st-<?= (int) $s['id'] ?>" name="status">
                    <?php foreach ($statuses as $st): ?>
                      <option value="<?= e($st) ?>" <?= $s['status'] === $st ? 'selected' : '' ?>>
                        <?= e(Skill::statusLabel($st)) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="field">
                  <label class="label" for="vis-<?= (int) $s['id'] ?>">Visibilidad</label>
                  <select class="select" id="vis-<?= (int) $s['id'] ?>" name="visibility">
                    <?php foreach (Visibility::all() as $vv): ?>
                      <option value="<?= e($vv) ?>" <?= $s['visibility'] === $vv ? 'selected' : '' ?>>
                        <?= e(Visibility::label($vv)) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <p class="hint"><?= e(Visibility::hint((string) $s['visibility'])) ?></p>
                </div>

                <div class="field">
                  <label class="label" for="rn-<?= (int) $s['id'] ?>">Nota para el autor</label>
                  <input class="input" type="text" id="rn-<?= (int) $s['id'] ?>" name="review_notes" maxlength="500"
                         value="<?= e((string) $s['review_notes']) ?>"
                         placeholder="Se envía por correo al aprobar, publicar o rechazar.">
                </div>
              </div>
              <button class="btn btn-primary btn-sm" type="submit">Aplicar <?= btnIcon('check') ?></button>
            </form>
          </details>
        </div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="shellbox">
      <div class="core empty">
        <span class="glyph"><?= icon('skill', 22) ?></span>
        <h3>Ninguna habilidad coincide</h3>
        <p>Prueba a limpiar los filtros.</p>
        <a class="btn btn-ghost btn-sm mt-1" href="<?= url('/admin/skills') ?>">Limpiar</a>
      </div>
    </div>
  <?php endif; ?>
</div>

<?= View::partial('partials/pager', ['page' => $result['page'], 'pages' => $result['pages'], 'path' => '/admin/skills']) ?>
