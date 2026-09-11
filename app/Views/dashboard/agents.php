<?php
/** @var array $agents */
use App\Core\Csrf;
use App\Core\Str;
use App\Core\View;
use App\Core\Visibility;
use App\Models\Skill;
?>
<div class="page-head">
  <div>
    <span class="eyebrow"><span class="dot"></span>Contenido</span>
    <h1 style="margin-top:.9rem">Mis agentes</h1>
    <p>Reglas en <span class="mono">.md</span> más las habilidades que les enlaces.</p>
  </div>
  <div class="btn-row">
    <a class="btn btn-primary btn-sm" href="<?= url('/dashboard/agents/new') ?>">Nuevo agente <?= btnIcon('plus') ?></a>
  </div>
</div>

<div class="shellbox">
  <div class="core">
    <?php if ($agents): ?>
      <div class="table-wrap">
        <table class="data">
          <thead>
            <tr><th>Agente</th><th>Skills</th><th>Versión</th><th>Estado</th><th>Visibilidad</th><th class="num">Descargas</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($agents as $a): ?>
              <tr>
                <td>
                  <a class="row-main" href="<?= url('/agents/' . $a['slug']) ?>"><?= e($a['name']) ?></a>
                  <span class="row-sub"><?= e($a['role_title'] ?: Str::excerpt((string) $a['short_description'], 60)) ?></span>
                </td>
                <td class="mono"><?= (int) $a['skills_count'] ?></td>
                <td class="mono">v<?= e($a['version']) ?></td>
                <td><span class="badge <?= $a['status'] === 'published' ? 'badge-mint' : '' ?>">
                  <?= e(Skill::statusLabel((string) $a['status'])) ?></span></td>
                <td>
                  <form method="post" action="<?= url('/dashboard/agents/' . $a['id'] . '/status') ?>">
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
                    <form method="post" action="<?= url('/dashboard/agents/' . $a['id'] . '/status') ?>">
                      <?= Csrf::field() ?>
                      <input type="hidden" name="status" value="<?= $a['status'] === 'published' ? 'draft' : 'published' ?>">
                      <button class="btn btn-ghost btn-sm" type="submit">
                        <?= $a['status'] === 'published' ? 'Despublicar' : 'Publicar' ?>
                      </button>
                    </form>
                    <a class="btn btn-ghost btn-sm" href="<?= url('/dashboard/agents/' . $a['id'] . '/edit') ?>">Editar</a>
                    <form method="post" action="<?= url('/dashboard/agents/' . $a['id'] . '/delete') ?>"
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
        <h3>Sin agentes todavía</h3>
        <p>Escribe las reglas una vez, enlaza las habilidades y ya tienes un agente descargable.</p>
        <a class="btn btn-primary btn-sm mt-1" href="<?= url('/dashboard/agents/new') ?>">Crear agente <?= btnIcon('plus') ?></a>
      </div>
    <?php endif; ?>
  </div>
</div>
