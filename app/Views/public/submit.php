<?php
/** @var array $categories @var array $compat @var array $errors @var int $maxBytes */
use App\Core\Csrf;
use App\Core\Str;

$err = static fn (string $k): string => isset($errors[$k]) ? ' has-error' : '';
?>
<section class="section-sm">
  <div class="shell shell-narrow">
    <div class="section-head">
      <span class="eyebrow"><span class="dot"></span>Sin cuenta</span>
      <h1 style="font-size:clamp(2rem,4.6vw,3rem);margin-top:1.2rem">Enviar una skill</h1>
      <p class="lede mt-2">
        No necesitas registrarte. Sólo tu correo, para avisarte si se publica o si hay que corregir algo.
        Una persona revisa cada envío antes de que aparezca en la biblioteca.
      </p>
    </div>

    <?php if (!empty($errors['general'])): ?>
      <div class="notice danger mb-2"><?= icon('alert', 17) ?><span><?= e($errors['general']) ?></span></div>
    <?php endif; ?>

    <form method="post" action="<?= url('/submit') ?>" enctype="multipart/form-data" novalidate>
      <?= Csrf::field() ?>
      <!-- Trampa para robots: invisible y sin etiqueta accesible. -->
      <div style="position:absolute;left:-9999px" aria-hidden="true">
        <label>No rellenar<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
      </div>

      <div class="shellbox">
        <div class="core pad-lg">
          <div class="field">
            <span class="label">¿Qué estás enviando? <span class="req">*</span></span>
            <div class="check-row">
              <label class="check">
                <input type="radio" name="kind" value="skill" <?= old('kind', 'skill') === 'skill' ? 'checked' : '' ?>>
                <span>
                  <span class="t">Una habilidad</span>
                  <span class="d">Se publica como .json y .md</span>
                </span>
              </label>
              <label class="check">
                <input type="radio" name="kind" value="agent" <?= old('kind') === 'agent' ? 'checked' : '' ?>>
                <span>
                  <span class="t">Un agente completo</span>
                  <span class="d">Reglas en .md con sus habilidades</span>
                </span>
              </label>
            </div>
          </div>

          <div class="field<?= $err('skill_name') ?>">
            <label class="label" for="skill_name">Nombre <span class="req">*</span></label>
            <input class="input" type="text" id="skill_name" name="skill_name" maxlength="140" required
                   value="<?= e(old('skill_name')) ?>" placeholder="Conciliador de IVA mensual">
            <?php if (isset($errors['skill_name'])): ?><p class="error-text"><?= e($errors['skill_name']) ?></p><?php endif; ?>
          </div>

          <div class="form-grid">
            <div class="field<?= $err('category_id') ?>">
              <label class="label" for="category_id">Categoría</label>
              <select class="select" id="category_id" name="category_id">
                <option value="">Sin categoría</option>
                <?php foreach ($categories as $c): ?>
                  <option value="<?= (int) $c['id'] ?>" <?= (string) old('category_id') === (string) $c['id'] ? 'selected' : '' ?>>
                    <?= e($c['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="field">
              <label class="label" for="author_name">Tu nombre</label>
              <input class="input" type="text" id="author_name" name="author_name" maxlength="120"
                     value="<?= e(old('author_name')) ?>" placeholder="Opcional, para darte crédito">
            </div>
          </div>

          <div class="field<?= $err('compatibility') ?>">
            <span class="label">Compatibilidad <span class="req">*</span></span>
            <div class="check-row">
              <?php $selected = (array) old('compatibility', ['OpenClaw']);
              foreach ($compat as $c): ?>
                <label class="check">
                  <input type="checkbox" name="compatibility[]" value="<?= e($c) ?>"
                         <?= in_array($c, $selected, true) ? 'checked' : '' ?>>
                  <span class="t"><?= e($c) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
            <?php if (isset($errors['compatibility'])): ?><p class="error-text"><?= e($errors['compatibility']) ?></p><?php endif; ?>
          </div>

          <div class="field<?= $err('description') ?>">
            <label class="label" for="description">
              Descripción y contenido <span class="req">*</span>
              <span class="push faint text-xs mono" id="descCount"></span>
            </label>
            <textarea class="textarea code" id="description" name="description" required maxlength="20000"
                      data-counter="#descCount"
                      placeholder="## Objetivo&#10;Qué resuelve la habilidad.&#10;&#10;## Instrucciones&#10;Cómo debe comportarse el agente.&#10;&#10;## Reglas&#10;- Qué nunca debe hacer."><?= e(old('description')) ?></textarea>
            <p class="hint">
              Escribe en Markdown usando secciones (<span class="mono">## Objetivo</span>,
              <span class="mono">## Instrucciones</span>, <span class="mono">## Workflow</span>,
              <span class="mono">## Reglas</span>, <span class="mono">## Inputs</span>,
              <span class="mono">## Outputs</span>). De ahí sale el JSON automáticamente.
            </p>
            <?php if (isset($errors['description'])): ?><p class="error-text"><?= e($errors['description']) ?></p><?php endif; ?>
          </div>

          <div class="field<?= $err('file') ?>">
            <span class="label">Archivo (opcional)</span>
            <label class="dropzone" tabindex="0">
              <?= icon('upload', 22) ?>
              <span class="t">Arrastra el archivo o pulsa para elegirlo</span>
              <span class="d">.md · .txt · .json · .zip — hasta <?= e(Str::humanBytes($maxBytes)) ?></span>
              <input type="file" name="file" accept=".md,.txt,.json,.zip,text/markdown,text/plain,application/json,application/zip">
            </label>
            <p class="hint">Si subes un archivo de texto usamos su contenido como base de la publicación.</p>
            <?php if (isset($errors['file'])): ?><p class="error-text"><?= e($errors['file']) ?></p><?php endif; ?>
          </div>

          <div class="field<?= $err('email') ?>">
            <label class="label" for="email">Correo electrónico <span class="req">*</span></label>
            <input class="input" type="email" id="email" name="email" required maxlength="190"
                   value="<?= e(old('email')) ?>" placeholder="tu@email.com" autocomplete="email">
            <p class="hint">Sólo lo usamos para avisarte del estado de este envío.</p>
            <?php if (isset($errors['email'])): ?><p class="error-text"><?= e($errors['email']) ?></p><?php endif; ?>
          </div>

          <div class="field">
            <label class="label" for="comments">Comentarios para quien revise</label>
            <textarea class="textarea" id="comments" name="comments" maxlength="2000" style="min-height:100px"
                      placeholder="Contexto, limitaciones conocidas, de dónde salió…"><?= e(old('comments')) ?></textarea>
          </div>
        </div>
      </div>

      <!-- ----------------------------------------------------- Privacidad -->
      <div class="shellbox tight mt-2">
        <div class="core pad">
          <h3 style="font-size:.95rem">Qué hacemos con tus datos</h3>
          <ul class="text-sm muted mt-1" style="padding-left:1.1rem;line-height:1.7">
            <li>Guardamos tu correo, el nombre que indiques y el contenido enviado.</li>
            <li>El correo se usa para informarte si tu envío se aprueba, se publica o requiere cambios.</li>
            <li>No lo usamos para publicidad ni lo cedemos a terceros.</li>
            <li>Puedes pedir la baja de tus datos citando el identificador del envío.</li>
          </ul>
          <p class="text-sm mt-1">
            <a href="<?= url('/privacy') ?>" style="color:var(--sky);text-decoration:underline">Leer la política completa</a>
          </p>

          <div class="field<?= $err('consent') ?>" style="margin-top:1.1rem">
            <div class="check">
              <input type="checkbox" id="consent" name="consent" value="1" required <?= old('consent') ? 'checked' : '' ?>>
              <span>
                <label class="t" for="consent" style="cursor:pointer;display:block">
                  Autorizo el tratamiento de mi correo para gestionar este envío
                </label>
                <span class="d">Obligatorio para poder avisarte del resultado.</span>
              </span>
            </div>
            <?php if (isset($errors['consent'])): ?><p class="error-text"><?= e($errors['consent']) ?></p><?php endif; ?>
          </div>
        </div>
      </div>

      <div class="btn-row mt-2">
        <button type="submit" class="btn btn-primary">Enviar skill <?= btnIcon('arrow-up-right') ?></button>
        <a class="btn btn-ghost" href="<?= url('/skills') ?>">Cancelar</a>
      </div>
    </form>
  </div>
</section>
