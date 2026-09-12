<?php
/** @var array|null $agent @var array $linked @var array $available @var array $categories @var array $compat @var array $errors */
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Str;
use App\Core\View;

$isEdit = $agent !== null;
$action = $isEdit ? url('/dashboard/agents/' . $agent['id'] . '/edit') : url('/dashboard/agents/new');
$err    = static fn (string $k): string => isset($errors[$k]) ? ' has-error' : '';
$selCom = $isEdit ? Str::listFromCsv((string) $agent['compatibility']) : (array) old('compatibility', ['OpenClaw']);

$plantillaReglas = "# Nombre del agente\n\n"
    . "Eres un [rol]. Trabajas para [contexto].\n\n"
    . "## Cómo trabajas\n\n"
    . "- Respondes en español, directo y sin rodeos.\n"
    . "- Antes de actuar, confirmas qué datos tienes y cuáles faltan.\n\n"
    . "## Qué nunca haces\n\n"
    . "- No inventas cifras, fechas ni nombres.\n"
    . "- No tomas decisiones que requieran firma humana.\n\n"
    . "## Cuándo detenerte\n\n"
    . "Si falta un dato obligatorio, detente y pídelo antes de continuar.";
?>
<div class="page-head">
  <div>
    <span class="eyebrow"><span class="dot"></span><?= $isEdit ? 'Editar agente' : 'Nuevo agente' ?></span>
    <h1 style="margin-top:.9rem"><?= $isEdit ? e($agent['name']) : 'Crear un agente' ?></h1>
    <p>Las reglas se descargan como <span class="mono">AGENT.md</span>; cada habilidad, como <span class="mono">.json</span>.</p>
  </div>
  <?php if ($isEdit): ?>
    <div class="btn-row">
      <a class="btn btn-ghost btn-sm" href="<?= url('/agents/' . $agent['slug']) ?>">Ver ficha <?= btnIcon('eye') ?></a>
    </div>
  <?php endif; ?>
</div>

<form method="post" action="<?= e($action) ?>" novalidate>
  <?= Csrf::field() ?>

  <div class="detail-grid">
    <div class="stack">
      <div class="shellbox">
        <div class="core pad-lg">
          <div class="form-grid">
            <div class="field<?= $err('name') ?>">
              <label class="label" for="name">Nombre <span class="req">*</span></label>
              <input class="input" type="text" id="name" name="name" required maxlength="140"
                     value="<?= e($isEdit ? $agent['name'] : old('name')) ?>" placeholder="Analista tributario">
              <?php if (isset($errors['name'])): ?><p class="error-text"><?= e($errors['name']) ?></p><?php endif; ?>
            </div>
            <div class="field<?= $err('role_title') ?>">
              <label class="label" for="role_title">Rol</label>
              <input class="input" type="text" id="role_title" name="role_title" maxlength="140"
                     value="<?= e($isEdit ? (string) $agent['role_title'] : old('role_title')) ?>"
                     placeholder="Contabilidad · Ecuador">
            </div>
          </div>

          <div class="field<?= $err('short_description') ?>">
            <label class="label" for="short_description">
              Descripción <span class="req">*</span>
              <span class="push faint text-xs mono" id="agentShortCount"></span>
            </label>
            <input class="input" type="text" id="short_description" name="short_description" required maxlength="255"
                   data-counter="#agentShortCount"
                   value="<?= e($isEdit ? $agent['short_description'] : old('short_description')) ?>"
                   placeholder="Revisa comprobantes, concilia IVA y arma el ATS mensual.">
            <?php if (isset($errors['short_description'])): ?><p class="error-text"><?= e($errors['short_description']) ?></p><?php endif; ?>
          </div>

          <div class="field<?= $err('rules_md') ?>">
            <label class="label" for="rules_md">Reglas del agente · AGENT.md <span class="req">*</span></label>
            <textarea class="textarea code" id="rules_md" name="rules_md" required><?= e($isEdit ? $agent['rules_md'] : old('rules_md', $plantillaReglas)) ?></textarea>
            <p class="hint">
              Este texto es literalmente lo que se descarga. Le añadimos el front-matter con la versión
              y la lista de habilidades; el cuerpo no se toca.
            </p>
            <?php if (isset($errors['rules_md'])): ?><p class="error-text"><?= e($errors['rules_md']) ?></p><?php endif; ?>
          </div>

          <div class="field<?= $err('system_prompt') ?>">
            <label class="label" for="system_prompt">Instrucción de sistema (opcional)</label>
            <textarea class="textarea" id="system_prompt" name="system_prompt" maxlength="2000" style="min-height:100px"
                      placeholder="Versión de una sola frase para agentes que sólo aceptan un prompt corto."><?= e($isEdit ? (string) $agent['system_prompt'] : old('system_prompt')) ?></textarea>
            <p class="hint">Va al campo <span class="mono">system_prompt</span> del manifiesto.</p>
          </div>
        </div>
      </div>

      <!-- ------------------------------------------------ Habilidades -->
      <div class="shellbox">
        <div class="core">
          <div class="pad" style="border-bottom:1px solid var(--line)">
            <h2 style="font-size:1.05rem">Habilidades del agente</h2>
            <p class="text-sm muted mt-1" style="margin-bottom:0">
              Marca las que se empaquetan con él. Las que marques como <strong>base</strong> no se pueden
              quitar desde la ficha pública.
            </p>
          </div>

          <?php if ($available): ?>
            <div class="pad stack-sm" style="max-height:480px;overflow:auto">
              <?php foreach ($available as $s):
                  $checked = in_array((int) $s['id'], array_map('intval', $linked), true);
                  $idLink  = 'sk' . (int) $s['id'];
                  $idReq   = 'rq' . (int) $s['id']; ?>
                <div class="check">
                  <input type="checkbox" id="<?= $idLink ?>" name="skills[]" value="<?= (int) $s['id'] ?>" <?= $checked ? 'checked' : '' ?>>
                  <label for="<?= $idLink ?>" style="flex:1;min-width:0;cursor:pointer">
                    <span class="t">
                      <?= e($s['name']) ?>
                      <span class="mono faint" style="font-size:.72rem">v<?= e($s['version']) ?></span>
                      <?php if ((int) $s['user_id'] === Auth::id()): ?>
                        <span class="badge" style="margin-left:.3rem">tuya</span>
                      <?php endif; ?>
                      <?php if ($s['status'] !== 'published'): ?>
                        <span class="badge badge-amber" style="margin-left:.3rem">sin publicar</span>
                      <?php endif; ?>
                    </span>
                    <span class="d"><?= e(Str::excerpt((string) $s['short_description'], 100)) ?></span>
                  </label>
                  <span class="row-tight text-xs muted" style="flex:none;gap:.3rem">
                    <input type="checkbox" id="<?= $idReq ?>" name="required[]" value="<?= (int) $s['id'] ?>"
                           style="width:13px;height:13px;margin:0">
                    <label for="<?= $idReq ?>" style="cursor:pointer">base</label>
                  </span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="empty">
              <span class="glyph"><?= icon('skill', 22) ?></span>
              <h3>No hay habilidades disponibles</h3>
              <p>Crea una habilidad primero y vuelve aquí para enlazarla.</p>
              <a class="btn btn-primary btn-sm mt-1" href="<?= url('/dashboard/skills/new') ?>">Nueva skill <?= btnIcon('plus') ?></a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- ---------------------------------------------------------- Lateral -->
    <aside class="side-stack">
      <div class="shellbox tight">
        <div class="core pad">
          <h3 style="font-size:.95rem">Publicación</h3>
          <div class="btn-row mt-2" style="flex-direction:column;align-items:stretch">
            <button class="btn btn-primary btn-block" type="submit" name="action" value="publish">
              <?= $isEdit && $agent['status'] === 'published' ? 'Guardar y mantener publicado' : 'Publicar agente' ?>
              <?= btnIcon('check') ?>
            </button>
            <button class="btn btn-ghost btn-block" type="submit" name="action" value="draft">Guardar borrador</button>
            <?php if ($isEdit && $agent['status'] === 'published'): ?>
              <button class="btn btn-ghost btn-block" type="submit" name="action" value="unpublish">Despublicar</button>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <?= View::partial('partials/tier-fields', ['row' => $agent]) ?>

      <div class="shellbox tight">
        <div class="core pad">
          <h3 style="font-size:.95rem">Clasificación</h3>

          <div class="field<?= $err('categories') ?> mt-2">
            <span class="label">Categorías <span class="req">*</span></span>
            <div class="stack-sm" style="max-height:240px;overflow:auto">
              <?php
              $catsSel = $isEdit ? array_map('intval', $cats) : array_map('intval', (array) old('categories', []));
              foreach ($categories as $c): ?>
                <label class="check">
                  <input type="checkbox" name="categories[]" value="<?= (int) $c['id'] ?>"
                         <?= in_array((int) $c['id'], $catsSel, true) ? 'checked' : '' ?>>
                  <span class="t"><?= e($c['name']) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
            <p class="hint">
              Puedes marcar varias. La de posición más alta queda como principal
              en la insignia y en los listados.
            </p>
            <?php if (isset($errors['categories'])): ?><p class="error-text"><?= e($errors['categories']) ?></p><?php endif; ?>
          </div>

          <div class="field">
            <label class="label" for="tags">Etiquetas</label>
            <input class="input" type="text" id="tags" name="tags" maxlength="255"
                   value="<?= e($isEdit ? (string) $agent['tags'] : old('tags')) ?>"
                   placeholder="contabilidad, sri, pymes">
          </div>

          <div class="field<?= $err('compatibility') ?>">
            <span class="label">Compatibilidad <span class="req">*</span></span>
            <div class="stack-sm">
              <?php foreach ($compat as $c): ?>
                <label class="check">
                  <input type="checkbox" name="compatibility[]" value="<?= e($c) ?>"
                         <?= in_array($c, $selCom, true) ? 'checked' : '' ?>>
                  <span class="t"><?= e($c) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
            <?php if (isset($errors['compatibility'])): ?><p class="error-text"><?= e($errors['compatibility']) ?></p><?php endif; ?>
          </div>

          <div class="field">
            <label class="label" for="visibility">Visibilidad</label>
            <select class="select" id="visibility" name="visibility">
              <?php
              $vis = ['public' => 'Público', 'unlisted' => 'Sólo con enlace', 'private' => 'Privado'];
              $cur = $isEdit ? (string) $agent['visibility'] : (string) old('visibility', 'public');
              foreach ($vis as $k => $label): ?>
                <option value="<?= e($k) ?>" <?= $cur === $k ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="field">
            <label class="label" for="version">Versión</label>
            <input class="input mono" type="text" id="version" name="version" maxlength="20"
                   value="<?= e($isEdit ? $agent['version'] : old('version', '1.0.0')) ?>">
          </div>

          <?php if ($isEdit): ?>
            <div class="field">
              <label class="label" for="bump">Incrementar</label>
              <select class="select" id="bump" name="bump">
                <option value="">No cambiar</option>
                <option value="patch">Parche</option>
                <option value="minor">Menor</option>
                <option value="major">Mayor</option>
              </select>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </aside>
  </div>
</form>

<?php if ($isEdit): ?>
  <form method="post" action="<?= url('/dashboard/agents/' . $agent['id'] . '/delete') ?>" class="mt-3"
        data-confirm="Se elimina «<?= e($agent['name']) ?>» de forma permanente. ¿Continuar?">
    <?= Csrf::field() ?>
    <div class="notice danger">
      <?= icon('alert', 17) ?>
      <span style="flex:1">
        <strong>Eliminar este agente.</strong> Las habilidades enlazadas no se borran, sólo deja de existir el agente.
      </span>
      <button class="btn btn-danger btn-sm" type="submit">Eliminar <?= btnIcon('trash') ?></button>
    </div>
  </form>
<?php endif; ?>
