<?php
/** @var array $skills @var string $status */
use App\Core\Csrf;
use App\Core\Str;
use App\Core\View;
use App\Core\Visibility;
use App\Models\Skill;

$tabs = ['' => 'Todas', 'published' => 'Publicadas', 'draft' => 'Borradores', 'archived' => 'Archivadas'];
?>
<div class="page-head">
  <div>
    <span class="eyebrow"><span class="dot"></span>Contenido</span>
    <h1 style="margin-top:.9rem">Mis habilidades</h1>
    <p>Cada una se descarga en <span class="mono">.json</span> y en <span class="mono">.md</span>.</p>
  </div>
  <div class="btn-row">
    <a class="btn btn-primary btn-sm" href="<?= url('/dashboard/skills/new') ?>">Nueva skill <?= btnIcon('plus') ?></a>
  </div>
</div>

<div class="tabs mb-2">
  <?php foreach ($tabs as $k => $label): ?>
    <a class="tab <?= $status === $k ? 'is-active' : '' ?>"
       href="<?= url('/dashboard/skills') ?><?= $k !== '' ? '?status=' . e($k) : '' ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</div>

<div class="shellbox">
  <div class="core">
    <?php if ($skills): ?>
      <div class="table-wrap">
        <table class="data">
          <thead>
            <tr><th>Skill</th><th>Versión</th><th>Estado</th><th>Visibilidad</th><th class="num">Descargas</th><th>Actualizada</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($skills as $s): ?>
              <tr>
                <td>
                  <a class="row-main" href="<?= url('/skills/' . $s['slug']) ?>"><?= e($s['name']) ?></a>
                  <span class="row-sub"><?= e(Str::excerpt((string) $s['short_description'], 70)) ?></span>
                </td>
                <td class="mono">v<?= e($s['version']) ?></td>
                <td><span class="badge <?= $s['status'] === 'published' ? 'badge-mint' : ($s['status'] === 'rejected' ? 'badge-danger' : '') ?>">
                  <?= e(Skill::statusLabel((string) $s['status'])) ?></span></td>
                <td>
                  <form method="post" action="<?= url('/dashboard/skills/' . $s['id'] . '/status') ?>">
                    <?= Csrf::field() ?>
                    <select class="select" name="visibility" data-autosubmit
                            style="padding:.4rem .6rem;font-size:.78rem;min-width:118px"
                            aria-label="Visibilidad de <?= e($s['name']) ?>">
                      <?php foreach (Visibility::all() as $vv): ?>
                        <option value="<?= e($vv) ?>" <?= $s['visibility'] === $vv ? 'selected' : '' ?>>
                          <?= e(Visibility::label($vv)) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <noscript><button class="btn btn-ghost btn-sm" type="submit">Aplicar</button></noscript>
                  </form>
                </td>
                <td class="num"><?= e(Str::compactNumber((int) $s['downloads'])) ?></td>
                <td class="text-xs muted nowrap"><?= e(Str::timeAgo((string) $s['updated_at'])) ?></td>
                <td>
                  <div class="row-actions">
                    <?php if ($s['status'] === 'published'): ?>
                      <form method="post" action="<?= url('/dashboard/skills/' . $s['id'] . '/status') ?>">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="status" value="draft">
                        <button class="btn btn-ghost btn-sm" type="submit">Despublicar</button>
                      </form>
                    <?php else: ?>
                      <form method="post" action="<?= url('/dashboard/skills/' . $s['id'] . '/status') ?>">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="status" value="published">
                        <button class="btn btn-ghost btn-sm" type="submit">Publicar</button>
                      </form>
                    <?php endif; ?>
                    <a class="btn btn-ghost btn-sm" href="<?= url('/dashboard/skills/' . $s['id'] . '/edit') ?>">Editar</a>
                    <form method="post" action="<?= url('/dashboard/skills/' . $s['id'] . '/delete') ?>"
                          data-confirm="Se elimina «<?= e($s['name']) ?>» y sus descargas dejan de funcionar. ¿Continuar?">
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
        <span class="glyph"><?= icon('skill', 22) ?></span>
        <h3>Nada por aquí</h3>
        <p>Crea tu primera habilidad: escribes el contenido en Markdown y el JSON se genera solo.</p>
        <a class="btn btn-primary btn-sm mt-1" href="<?= url('/dashboard/skills/new') ?>">Nueva skill <?= btnIcon('plus') ?></a>
      </div>
    <?php endif; ?>
  </div>
</div>
