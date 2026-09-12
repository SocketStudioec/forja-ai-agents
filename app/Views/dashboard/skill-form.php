<?php
/** @var array|null $skill @var array $categories @var array $compat @var array $errors @var array $versions */
use App\Core\Csrf;
use App\Core\Str;
use App\Core\View;

$isEdit  = $skill !== null;
$action  = $isEdit ? url('/dashboard/skills/' . $skill['id'] . '/edit') : url('/dashboard/skills/new');
$err     = static fn (string $k): string => isset($errors[$k]) ? ' has-error' : '';
$selCom  = $isEdit ? Str::listFromCsv((string) $skill['compatibility']) : (array) old('compatibility', ['OpenClaw']);
$selFmt  = $isEdit ? Str::listFromCsv((string) $skill['formats']) : ['md', 'txt', 'json', 'zip'];

$plantilla = "## Objetivo\n\nQué resuelve esta habilidad y cuándo usarla.\n\n"
    . "## Instrucciones\n\nCómo debe comportarse el agente al ejecutarla.\n\n"
    . "## Workflow\n\n1. Primer paso.\n2. Segundo paso.\n3. Resultado.\n\n"
    . "## Reglas\n\n- Nunca inventes datos que no estén en la fuente.\n- Si falta información, pídela.\n\n"
    . "## Inputs\n\n- dato_uno: qué es\n\n## Outputs\n\n- resultado: qué devuelve\n\n"
    . "## Ejemplos\n\nUn caso real, con entrada y salida.";
?>
<div class="page-head">
  <div>
    <span class="eyebrow"><span class="dot"></span><?= $isEdit ? 'Editar' : 'Crear' ?></span>
    <h1 style="margin-top:.9rem"><?= $isEdit ? e($skill['name']) : 'Nueva habilidad' ?></h1>
    <p><?= $isEdit ? 'Los cambios regeneran los formatos descargables al instante.' : 'Escribe en Markdown; el JSON se deriva solo.' ?></p>
  </div>
  <?php if ($isEdit): ?>
    <div class="btn-row">
      <a class="btn btn-ghost btn-sm" href="<?= url('/skills/' . $skill['slug']) ?>">Ver ficha <?= btnIcon('eye') ?></a>
    </div>
  <?php endif; ?>
</div>

<form method="post" action="<?= e($action) ?>" novalidate>
  <?= Csrf::field() ?>

  <div class="detail-grid">
    <div class="stack">
      <div class="shellbox">
        <div class="core pad-lg">
          <div class="field<?= $err('name') ?>">
            <label class="label" for="name">Nombre <span class="req">*</span></label>
            <input class="input" type="text" id="name" name="name" required maxlength="140"
                   value="<?= e($isEdit ? $skill['name'] : old('name')) ?>" placeholder="Conciliar IVA mensual">
            <?php if ($isEdit): ?>
              <p class="hint">Enlace público: <span class="mono"><?= e(url('/skills/' . $skill['slug'])) ?></span>
              <?= $skill['status'] === 'published' ? '— ya publicado, el enlace no cambia.' : '' ?></p>
            <?php endif; ?>
            <?php if (isset($errors['name'])): ?><p class="error-text"><?= e($errors['name']) ?></p><?php endif; ?>
          </div>

          <div class="field<?= $err('short_description') ?>">
            <label class="label" for="short_description">
              Descripción corta <span class="req">*</span>
              <span class="push faint text-xs mono" id="shortCount"></span>
            </label>
            <input class="input" type="text" id="short_description" name="short_description" required maxlength="255"
                   data-counter="#shortCount"
                   value="<?= e($isEdit ? $skill['short_description'] : old('short_description')) ?>"
                   placeholder="Cruza el libro de compras con el ATS y reporta diferencias.">
            <p class="hint">Es lo que se ve en la tarjeta del catálogo y en el campo <span class="mono">description</span> del JSON.</p>
            <?php if (isset($errors['short_description'])): ?><p class="error-text"><?= e($errors['short_description']) ?></p><?php endif; ?>
          </div>

          <div class="field<?= $err('description') ?>">
            <label class="label" for="description">Contenido en Markdown <span class="req">*</span></label>
            <textarea class="textarea code" id="description" name="description" required><?= e($isEdit ? $skill['description'] : old('description', $plantilla)) ?></textarea>
            <p class="hint">
              Usa los títulos <span class="mono">## Objetivo</span>, <span class="mono">## Instrucciones</span>,
              <span class="mono">## Workflow</span>, <span class="mono">## Reglas</span>,
              <span class="mono">## Inputs</span>, <span class="mono">## Outputs</span> y
              <span class="mono">## Ejemplos</span>: cada uno se convierte en un campo del JSON.
            </p>
            <?php if (isset($errors['description'])): ?><p class="error-text"><?= e($errors['description']) ?></p><?php endif; ?>
          </div>
        </div>
      </div>

      <div class="shellbox">
        <div class="core pad-lg">
          <h2 style="font-size:1.05rem">JSON personalizado (opcional)</h2>
          <p class="text-sm muted mt-1">
            Si necesitas campos que el Markdown no cubre, pega aquí un objeto JSON. Se fusiona
            encima de lo derivado automáticamente, así que sólo tienes que escribir lo que cambia.
          </p>
          <div class="field<?= $err('definition_json') ?>" style="margin-top:1rem">
            <label class="sr-only" for="definition_json">JSON personalizado</label>
            <textarea class="textarea code" id="definition_json" name="definition_json" style="min-height:170px"
                      placeholder='{"tools": ["lector_pdf"], "max_tokens": 4000}'><?= e($isEdit ? (string) $skill['definition_json'] : old('definition_json')) ?></textarea>
            <?php if (isset($errors['definition_json'])): ?><p class="error-text"><?= e($errors['definition_json']) ?></p><?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- ------------------------------------------------------- Lateral -->
    <aside class="side-stack">
      <div class="shellbox tight">
        <div class="core pad">
          <h3 style="font-size:.95rem">Publicación</h3>
          <div class="btn-row mt-2" style="flex-direction:column;align-items:stretch">
            <button class="btn btn-primary btn-block" type="submit" name="action" value="publish">
              <?= $isEdit && $skill['status'] === 'published' ? 'Guardar y mantener publicada' : 'Publicar' ?>
              <?= btnIcon('check') ?>
            </button>
            <button class="btn btn-ghost btn-block" type="submit" name="action" value="draft">
              Guardar borrador
            </button>
            <?php if ($isEdit && $skill['status'] === 'published'): ?>
              <button class="btn btn-ghost btn-block" type="submit" name="action" value="unpublish">
                Despublicar
              </button>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <?= View::partial('partials/tier-fields', ['row' => $skill]) ?>

      <div class="shellbox tight">
        <div class="core pad">
          <h3 style="font-size:.95rem">Clasificación</h3>

          <div class="field mt-2">
            <label class="label" for="category_id">Categoría</label>
            <select class="select" id="category_id" name="category_id">
              <option value="">Sin categoría</option>
              <?php foreach ($categories as $c):
                  $sel = $isEdit ? (int) $skill['category_id'] === (int) $c['id'] : (string) old('category_id') === (string) $c['id']; ?>
                <option value="<?= (int) $c['id'] ?>" <?= $sel ? 'selected' : '' ?>><?= e($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="field">
            <label class="label" for="tags">Etiquetas</label>
            <input class="input" type="text" id="tags" name="tags" maxlength="255"
                   value="<?= e($isEdit ? (string) $skill['tags'] : old('tags')) ?>"
                   placeholder="contabilidad, sri, conciliación">
            <p class="hint">Separadas por comas.</p>
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
              $vis = ['public' => 'Pública — aparece en el catálogo', 'unlisted' => 'Con enlace — no aparece en listas', 'private' => 'Privada — sólo tú'];
              $cur = $isEdit ? (string) $skill['visibility'] : (string) old('visibility', 'public');
              foreach ($vis as $k => $label): ?>
                <option value="<?= e($k) ?>" <?= $cur === $k ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div class="shellbox tight">
        <div class="core pad">
          <h3 style="font-size:.95rem">Formatos descargables</h3>
          <p class="hint" style="margin-top:.4rem">
            <span class="mono">.json</span> y <span class="mono">.md</span> están siempre disponibles.
          </p>
          <div class="stack-sm mt-2">
            <?php
            $fmts  = ['md' => 'SKILL.md', 'txt' => 'Texto plano', 'json' => 'Definición JSON', 'zip' => 'Paquete ZIP'];
            $fixed = ['md', 'json'];   // el par base del producto, no se desactiva
            foreach ($fmts as $k => $label):
                $isFixed = in_array($k, $fixed, true); ?>
              <label class="check">
                <?php if ($isFixed): ?>
                  <input type="checkbox" checked disabled aria-label="<?= e($label) ?> (siempre activo)">
                  <input type="hidden" name="formats[]" value="<?= e($k) ?>">
                <?php else: ?>
                  <input type="checkbox" name="formats[]" value="<?= e($k) ?>"
                         <?= in_array($k, $selFmt, true) ? 'checked' : '' ?>>
                <?php endif; ?>
                <span class="t">
                  <?= e($label) ?> <span class="mono faint">.<?= e($k) ?></span>
                  <?php if ($isFixed): ?><span class="badge badge-mint" style="margin-left:.3rem">fijo</span><?php endif; ?>
                </span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="shellbox tight">
        <div class="core pad">
          <h3 style="font-size:.95rem">Versión</h3>
          <div class="field mt-2">
            <label class="sr-only" for="version">Número de versión</label>
            <input class="input mono" type="text" id="version" name="version" maxlength="20"
                   value="<?= e($isEdit ? $skill['version'] : old('version', '1.0.0')) ?>" placeholder="1.0.0">
          </div>

          <?php if ($isEdit): ?>
            <div class="field">
              <label class="label" for="bump">O incrementa automáticamente</label>
              <select class="select" id="bump" name="bump">
                <option value="">No cambiar</option>
                <option value="patch">Parche — corrección</option>
                <option value="minor">Menor — añade algo</option>
                <option value="major">Mayor — cambia el comportamiento</option>
              </select>
            </div>
            <div class="field">
              <label class="label" for="changelog">Qué cambió</label>
              <input class="input" type="text" id="changelog" name="changelog" maxlength="255"
                     placeholder="Añadido el paso de normalización de RUC">
            </div>

            <?php if ($versions): ?>
              <div class="meta-list mt-2">
                <?php foreach (array_slice($versions, 0, 5) as $v): ?>
                  <div>
                    <span class="k mono">v<?= e($v['version']) ?></span>
                    <span class="v" style="font-size:.72rem"><?= e(date('d/m/Y', strtotime((string) $v['created_at']))) ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </aside>
  </div>
</form>

<?php if ($isEdit): ?>
  <form method="post" action="<?= url('/dashboard/skills/' . $skill['id'] . '/delete') ?>" class="mt-3"
        data-confirm="Se elimina «<?= e($skill['name']) ?>» de forma permanente. ¿Continuar?">
    <?= Csrf::field() ?>
    <div class="notice danger">
      <?= icon('alert', 17) ?>
      <span style="flex:1">
        <strong>Eliminar esta habilidad.</strong> Desaparece del catálogo y sus enlaces de descarga dejan de funcionar.
      </span>
      <button class="btn btn-danger btn-sm" type="submit">Eliminar <?= btnIcon('trash') ?></button>
    </div>
  </form>
<?php endif; ?>
