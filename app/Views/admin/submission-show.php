<?php
/** @var array $submission @var string $preview @var array $categories */
use App\Core\Csrf;
use App\Core\Str;
use App\Models\Submission;

$s      = $submission;
$tone   = Submission::statusTone((string) $s['status']);
$closed = in_array($s['status'], ['approved', 'published', 'rejected'], true);
?>
<div class="page-head">
  <div>
    <span class="eyebrow"><span class="dot"></span><?= e($s['reference']) ?></span>
    <h1 style="margin-top:.9rem"><?= e($s['skill_name']) ?></h1>
    <p>
      Enviado <?= e(Str::timeAgo((string) $s['created_at'])) ?>
      por <?= e($s['name'] ?: 'alguien sin nombre') ?>.
    </p>
  </div>
  <div class="btn-row">
    <a class="btn btn-ghost btn-sm" href="<?= url('/admin/submissions') ?>">Volver a la bandeja</a>
  </div>
</div>

<div class="detail-grid">
  <div class="stack">
    <!-- ------------------------------------------------------ Contenido -->
    <div class="shellbox">
      <div class="core">
        <div class="code-head">
          <span class="lights" aria-hidden="true"><i></i><i></i><i></i></span>
          <span class="name">Descripción del remitente</span>
          <span class="tag badge <?= $tone ? 'badge-' . e($tone) : '' ?>">
            <?= e(Submission::statusLabel((string) $s['status'])) ?>
          </span>
        </div>
        <div class="viewer"><pre><?= e($s['description']) ?></pre></div>
      </div>
    </div>

    <?php if ($preview !== ''): ?>
      <div class="shellbox">
        <div class="core">
          <div class="code-head">
            <span class="lights" aria-hidden="true"><i></i><i></i><i></i></span>
            <span class="name"><?= e($s['file_name']) ?></span>
            <span class="tag badge"><?= e(Str::humanBytes((int) $s['file_size'])) ?></span>
          </div>
          <div class="viewer"><pre><?= e($preview) ?></pre></div>
          <div class="pad" style="border-top:1px solid var(--line)">
            <a class="btn btn-ghost btn-sm" href="<?= url('/admin/submissions/' . $s['id'] . '/file') ?>">
              Descargar adjunto <?= btnIcon('download') ?>
            </a>
          </div>
        </div>
      </div>
    <?php elseif (!empty($s['file_name'])): ?>
      <div class="notice">
        <?= icon('info', 17) ?>
        <span style="flex:1">
          Adjuntó <strong><?= e($s['file_name']) ?></strong> (<?= e(Str::humanBytes((int) $s['file_size'])) ?>).
          No se puede previsualizar porque no es texto.
        </span>
        <a class="btn btn-ghost btn-sm" href="<?= url('/admin/submissions/' . $s['id'] . '/file') ?>">Descargar</a>
      </div>
    <?php endif; ?>

    <?php if (!empty($s['comments'])): ?>
      <div class="shellbox tight">
        <div class="core pad">
          <h3 style="font-size:.95rem">Comentarios del remitente</h3>
          <p class="text-sm muted mt-1"><?= nl2br(e($s['comments'])) ?></p>
        </div>
      </div>
    <?php endif; ?>

    <!-- -------------------------------------------------------- Decisión -->
    <div class="shellbox">
      <div class="core pad-lg">
        <h2 style="font-size:1.05rem">Decisión</h2>
        <p class="text-sm muted mt-1">
          Al aprobar se crea <?= $s['kind'] === 'agent' ? 'un agente' : 'una habilidad' ?> en el catálogo con
          este contenido. Al rechazar, el motivo se envía por correo al remitente.
        </p>

        <?php if ($closed): ?>
          <div class="notice mint mt-2">
            <?= icon('check', 17) ?>
            <span>
              Ya revisado
              <?php if (!empty($s['reviewed_at'])): ?>
                el <?= e(date('d/m/Y H:i', strtotime((string) $s['reviewed_at']))) ?>
              <?php endif; ?>
              <?php if (!empty($s['reviewer_username'])): ?> por <?= e($s['reviewer_username']) ?><?php endif; ?>.
              Puedes volver a aplicar una decisión si hace falta.
            </span>
          </div>
        <?php endif; ?>

        <form method="post" action="<?= url('/admin/submissions/' . $s['id'] . '/review') ?>" class="mt-3">
          <?= Csrf::field() ?>

          <div class="form-grid">
            <div class="field">
              <label class="label" for="category_id">Categoría al publicar</label>
              <select class="select" id="category_id" name="category_id">
                <option value="">Sin categoría</option>
                <?php foreach ($categories as $c): ?>
                  <option value="<?= (int) $c['id'] ?>" <?= (int) $s['category_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                    <?= e($c['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="field">
              <span class="label">Al aprobar</span>
              <div class="check">
                <input type="checkbox" id="publish" name="publish" value="1" checked>
                <label for="publish" style="flex:1;cursor:pointer">
                  <span class="t">Publicar de inmediato</span>
                  <span class="d">Si lo desmarcas queda aprobado pero oculto.</span>
                </label>
              </div>
            </div>
          </div>

          <div class="field">
            <label class="label" for="review_notes">Notas / motivo</label>
            <textarea class="textarea" id="review_notes" name="review_notes" style="min-height:110px"
                      placeholder="Obligatorio al rechazar. Se envía tal cual al remitente."><?= e((string) $s['review_notes']) ?></textarea>
            <p class="hint">Ejemplo: «La skill no contiene instrucciones suficientes para su utilización.»</p>
          </div>

          <div class="btn-row">
            <button class="btn btn-primary" type="submit" name="decision" value="approve">
              Aprobar <?= btnIcon('check') ?>
            </button>
            <button class="btn btn-ghost" type="submit" name="decision" value="under_review">
              Marcar en revisión
            </button>
            <button class="btn btn-danger" type="submit" name="decision" value="reject">
              Rechazar <?= btnIcon('x') ?>
            </button>
          </div>
        </form>
      </div>
    </div>

    <form method="post" action="<?= url('/admin/submissions/' . $s['id'] . '/delete') ?>"
          data-confirm="Se elimina el envío y su archivo adjunto de forma permanente. ¿Continuar?">
      <?= Csrf::field() ?>
      <div class="notice danger">
        <?= icon('alert', 17) ?>
        <span style="flex:1">
          <strong>Eliminar el envío.</strong> Borra también el archivo adjunto del servidor.
          Es la vía para atender una solicitud de supresión de datos.
        </span>
        <button class="btn btn-danger btn-sm" type="submit">Eliminar <?= btnIcon('trash') ?></button>
      </div>
    </form>
  </div>

  <aside class="side-stack">
    <div class="shellbox tight">
      <div class="core pad">
        <h3 style="font-size:.95rem">Datos del envío</h3>
        <div class="meta-list mt-1">
          <div><span class="k">Referencia</span><span class="v"><?= e($s['reference']) ?></span></div>
          <div><span class="k">Tipo</span><span class="v"><?= $s['kind'] === 'agent' ? 'Agente' : 'Skill' ?></span></div>
          <div><span class="k">Autor</span><span class="v"><?= e($s['name'] ?: '—') ?></span></div>
          <div><span class="k">Compatibilidad</span><span class="v" style="font-size:.72rem"><?= e($s['compatibility']) ?></span></div>
          <div><span class="k">Consentimiento</span><span class="v"><?= (int) $s['consent'] === 1 ? 'Sí' : 'No' ?></span></div>
          <div><span class="k">Recibido</span><span class="v" style="font-size:.72rem"><?= e(date('d/m/Y H:i', strtotime((string) $s['created_at']))) ?></span></div>
        </div>
      </div>
    </div>

    <div class="shellbox tight">
      <div class="core pad">
        <h3 style="font-size:.95rem">Contacto</h3>
        <p class="mono text-sm mt-1" style="word-break:break-all"><?= e($s['email']) ?></p>
        <div class="btn-row mt-2">
          <a class="btn btn-ghost btn-sm" href="mailto:<?= e($s['email']) ?>?subject=<?= rawurlencode('Sobre tu envío ' . $s['reference']) ?>">
            Escribir <?= btnIcon('mail') ?>
          </a>
          <button type="button" class="btn btn-ghost btn-sm" data-copy="<?= e($s['email']) ?>"
                  data-copy-msg="Correo copiado">Copiar</button>
        </div>
      </div>
    </div>

    <?php if (!empty($s['skill_id']) || !empty($s['agent_id'])): ?>
      <div class="shellbox tight">
        <div class="core pad">
          <h3 style="font-size:.95rem">Publicación creada</h3>
          <a class="btn btn-primary btn-sm btn-block mt-2"
             href="<?= !empty($s['agent_id']) ? url('/agents') : url('/skills') ?>">
            Ver en el catálogo <?= btnIcon('arrow') ?>
          </a>
        </div>
      </div>
    <?php endif; ?>
  </aside>
</div>
