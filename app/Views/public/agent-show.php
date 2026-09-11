<?php
/**
 * @var array $agent @var array $skills @var string $rulesHtml @var string $rulesRaw
 * @var string $jsonPreview @var bool $isFavorite @var bool $canEdit
 */
use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Str;
use App\Core\View;

$publicUrl = Config::absUrl('/agents/' . $agent['slug']);
$compat    = Str::listFromCsv($agent['compatibility'] ?? '');
$tags      = Str::listFromCsv($agent['tags'] ?? '');
$zipBase   = url('/agents/' . $agent['slug'] . '/download') . '?format=zip';
$metaDesc  = Str::excerpt((string) $agent['short_description'], 155);
?>
<section class="section-sm">
  <div class="shell">
    <nav class="row-tight text-sm muted" style="margin-bottom:1.6rem" aria-label="Ruta">
      <a href="<?= url('/agents') ?>">Agentes</a>
      <span class="faint">/</span>
      <span class="ink"><?= e($agent['name']) ?></span>
    </nav>

    <div class="detail-grid">
      <!-- ------------------------------------------------------- Principal -->
      <div>
        <div class="row" style="align-items:flex-start;gap:1rem">
          <span class="card-glyph" style="width:52px;height:52px;border-radius:15px;font-size:17px" aria-hidden="true">
            <?= e(glyphFor((string) $agent['name'])) ?>
          </span>
          <div style="min-width:0;flex:1">
            <?php if (!empty($agent['role_title'])): ?>
              <span class="eyebrow"><span class="dot"></span><?= e($agent['role_title']) ?></span>
            <?php endif; ?>
            <h1 style="font-size:clamp(1.8rem,4.2vw,2.8rem);margin-top:.8rem"><?= e($agent['name']) ?></h1>
          </div>
        </div>

        <p class="lede mt-2"><?= e($agent['short_description']) ?></p>

        <div class="chips mt-2">
          <span class="badge badge-mint">v<?= e($agent['version']) ?></span>
          <span class="badge badge-sky"><?= count($skills) ?> habilidad<?= count($skills) === 1 ? '' : 'es' ?></span>
          <?php if (!empty($agent['category_name'])): ?>
            <a class="badge" href="<?= url('/agents') ?>?category=<?= e($agent['category_slug']) ?>"><?= e($agent['category_name']) ?></a>
          <?php endif; ?>
          <?php foreach ($compat as $c): ?><span class="badge"><?= e($c) ?></span><?php endforeach; ?>
          <?php foreach ($tags as $t): ?><span class="badge">#<?= e($t) ?></span><?php endforeach; ?>
        </div>

        <?php if ($agent['visibility'] !== 'public' || $agent['status'] !== 'published'): ?>
          <div class="notice warn mt-2">
            <?= icon('eye-off', 17) ?>
            <span>
              Esta ficha no es pública todavía: estado <strong><?= e(App\Models\Skill::statusLabel((string) $agent['status'])) ?></strong>,
              visibilidad <strong><?= e($agent['visibility']) ?></strong>. La ves porque eres su autor o administrador.
            </span>
          </div>
        <?php endif; ?>

        <!-- ------------------------------------------ Selector de habilidades -->
        <div class="shellbox mt-3" id="skillPicker">
          <div class="core">
            <div class="pad" style="border-bottom:1px solid var(--line)">
              <div class="row">
                <div>
                  <h2 style="font-size:1.15rem">Habilidades del agente</h2>
                  <p class="text-sm muted mt-1" style="margin-bottom:0">
                    Marca sólo las que necesites. El ZIP y el manifiesto se ajustan a tu selección.
                  </p>
                </div>
                <?php if ($skills): ?>
                  <button type="button" class="btn btn-ghost btn-sm push" id="pickAll">Marcar / desmarcar todo</button>
                <?php endif; ?>
              </div>
            </div>

            <?php if ($skills): ?>
              <div class="pad stack-sm">
                <?php foreach ($skills as $s):
                    $required = (int) ($s['required'] ?? 0) === 1;
                    $boxId    = 'pk-' . e($s['slug']); ?>
                  <div class="check">
                    <input type="checkbox" id="<?= $boxId ?>" name="pick[]" value="<?= e($s['slug']) ?>" checked
                           <?= $required ? 'disabled' : '' ?>>
                    <span style="flex:1;min-width:0">
                      <label for="<?= $boxId ?>" class="t" style="cursor:pointer;display:block">
                        <?= e($s['name']) ?>
                        <span class="mono faint" style="font-size:.72rem">v<?= e($s['version']) ?></span>
                        <?php if ($required): ?>
                          <span class="badge badge-mint" style="margin-left:.3rem">base</span>
                        <?php endif; ?>
                      </label>
                      <span class="d"><?= e(Str::excerpt((string) $s['short_description'], 110)) ?></span>
                      <span class="row-tight mt-1" style="gap:.35rem">
                        <a class="skill-pill" href="<?= url('/skills/' . $s['slug'] . '/download') ?>?format=json">
                          <?= e($s['slug']) ?><span class="ext">.json</span>
                        </a>
                        <a class="skill-pill" href="<?= url('/skills/' . $s['slug']) ?>">ver ficha</a>
                      </span>
                    </span>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="empty">
                <span class="glyph"><?= icon('skill', 22) ?></span>
                <h3>Este agente todavía no tiene habilidades enlazadas</h3>
                <p>Puedes descargar sus reglas igualmente y añadirle habilidades sueltas de la biblioteca.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- --------------------------------------------- Reglas y manifiesto -->
        <div class="shellbox mt-3" data-tabs>
          <div class="core">
            <div class="code-head">
              <span class="lights" aria-hidden="true"><i></i><i></i><i></i></span>
              <span class="name">Contenido</span>
              <span class="push tabs" style="margin-left:auto">
                <button type="button" class="tab is-active" data-tab="rules">Reglas</button>
                <button type="button" class="tab" data-tab="raw">AGENT.md</button>
                <button type="button" class="tab" data-tab="json">agent.json</button>
              </span>
            </div>

            <div data-panel="rules" class="pad-lg prose"><?= $rulesHtml ?></div>

            <div data-panel="raw" hidden>
              <div class="viewer"><pre id="rawRules"><?= e($rulesRaw) ?></pre></div>
              <div class="pad" style="border-top:1px solid var(--line)">
                <button type="button" class="btn btn-ghost btn-sm" data-copy-target="#rawRules"
                        data-copy-msg="AGENT.md copiado">
                  Copiar AGENT.md <?= btnIcon('copy') ?>
                </button>
              </div>
            </div>

            <div data-panel="json" hidden>
              <div class="viewer"><pre id="rawJson"><?= e($jsonPreview) ?></pre></div>
              <div class="pad" style="border-top:1px solid var(--line)">
                <button type="button" class="btn btn-ghost btn-sm" data-copy-target="#rawJson"
                        data-copy-msg="Manifiesto copiado">
                  Copiar manifiesto <?= btnIcon('copy') ?>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- --------------------------------------------------------- Lateral -->
      <aside class="side-stack">
        <div class="shellbox tight">
          <div class="core pad">
            <h3 style="font-size:.95rem">Descargar</h3>
            <p class="text-sm muted mt-1" style="margin-bottom:1rem">
              <span data-skill-count><?= count($skills) ?></span> de <?= count($skills) ?> habilidades seleccionadas.
            </p>

            <div class="format-list">
              <a class="format-item" data-zip-base="<?= e($zipBase) ?>" href="<?= e($zipBase) ?>">
                <span class="ext">.zip</span>
                <span>
                  <span class="t">Paquete completo</span>
                  <span class="d">AGENT.md + skills/*.json + manifiesto</span>
                </span>
                <span class="go"><?= icon('download', 15) ?></span>
              </a>

              <a class="format-item" href="<?= url('/agents/' . $agent['slug'] . '/download') ?>?format=md">
                <span class="ext">.md</span>
                <span>
                  <span class="t">Sólo las reglas</span>
                  <span class="d">AGENT.md con front-matter</span>
                </span>
                <span class="go"><?= icon('download', 15) ?></span>
              </a>

              <a class="format-item" href="<?= url('/agents/' . $agent['slug'] . '/download') ?>?format=json">
                <span class="ext sky">.json</span>
                <span>
                  <span class="t">Manifiesto</span>
                  <span class="d">Metadatos y lista de habilidades</span>
                </span>
                <span class="go"><?= icon('download', 15) ?></span>
              </a>
            </div>

            <div class="stack-sm mt-2">
              <?= View::partial('partials/account-button', [
                  'type'   => 'agent',
                  'id'     => (int) $agent['id'],
                  'active' => $isFavorite,
                  'name'   => (string) $agent['name'],
                  'size'   => '',
                  'block'  => true,
              ]) ?>
              <button type="button" class="btn btn-ghost btn-sm btn-block"
                      data-cart-toggle="agent" data-slug="<?= e($agent['slug']) ?>"
                      data-add-label="Añadir a un paquete" aria-pressed="false">
                <span data-cart-label>Añadir a un paquete</span> <?= btnIcon('cart') ?>
              </button>
              <p class="hint" style="text-align:center">
                Un paquete combina varios agentes en un solo ZIP.
              </p>
            </div>
          </div>
        </div>

        <div class="shellbox tight">
          <div class="core pad">
            <div class="meta-list">
              <div><span class="k">Autor</span><span class="v"><?= e($agent['author_display']) ?></span></div>
              <div><span class="k">Versión</span><span class="v"><?= e($agent['version']) ?></span></div>
              <div><span class="k">Descargas</span><span class="v"><?= e(Str::compactNumber((int) $agent['downloads'])) ?></span></div>
              <div><span class="k">Vistas</span><span class="v"><?= e(Str::compactNumber((int) $agent['views'])) ?></span></div>
              <div><span class="k">Actualizado</span><span class="v"><?= e(date('d/m/Y', strtotime((string) $agent['updated_at']))) ?></span></div>
            </div>
          </div>
        </div>

        <div class="shellbox tight">
          <div class="core pad">
            <h3 style="font-size:.95rem">Compartir</h3>
            <div class="btn-row mt-1">
              <button type="button" class="btn btn-ghost btn-sm" data-copy="<?= e($publicUrl) ?>">
                Copiar enlace <?= btnIcon('link') ?>
              </button>
              <button type="button" class="btn btn-ghost btn-sm" data-share="<?= e($publicUrl) ?>"
                      data-share-title="<?= e($agent['name']) ?>">
                Compartir <?= btnIcon('share') ?>
              </button>
            </div>
            <div class="btn-row mt-1">
              <a class="btn btn-ghost btn-sm" rel="noopener" target="_blank"
                 href="https://wa.me/?text=<?= rawurlencode($agent['name'] . ' — ' . $publicUrl) ?>">WhatsApp</a>
              <a class="btn btn-ghost btn-sm"
                 href="mailto:?subject=<?= rawurlencode((string) $agent['name']) ?>&body=<?= rawurlencode($publicUrl) ?>">Correo</a>
            </div>
          </div>
        </div>

        <?php if ($canEdit): ?>
          <div class="shellbox tight">
            <div class="core pad">
              <h3 style="font-size:.95rem">Gestión</h3>
              <div class="btn-row mt-1">
                <a class="btn btn-ghost btn-sm" href="<?= url('/dashboard/agents/' . $agent['id'] . '/edit') ?>">
                  Editar <?= btnIcon('edit') ?>
                </a>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </aside>
    </div>
  </div>
</section>

<?= View::partial('partials/cart-bar') ?>
