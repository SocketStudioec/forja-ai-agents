<?php
/**
 * @var array $skill @var string $html @var string $jsonPreview @var array $formats
 * @var array $related @var array $agents @var array $versions @var bool $isFavorite @var bool $canEdit
 */
use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Packager;
use App\Core\Str;
use App\Core\Tier;
use App\Core\View;

$publicUrl = Config::absUrl('/skills/' . $skill['slug']);
$compat    = Str::listFromCsv($skill['compatibility'] ?? '');
$tags      = Str::listFromCsv($skill['tags'] ?? '');
$metaDesc  = Str::excerpt((string) $skill['short_description'], 155);

$formatInfo = [
    'json' => ['.json', 'Definición estructurada', 'El archivo que lee el agente', 'sky'],
    'md'   => ['.md',   'SKILL.md',                'Markdown con front-matter',   ''],
    'txt'  => ['.txt',  'Texto plano',             'Para pegar en cualquier chat', ''],
    'zip'  => ['.zip',  'Paquete completo',        'Los tres formatos y un README', ''],
];
$available = array_values(array_unique(array_merge(['json', 'md'], $formats)));
?>
<section class="section-sm">
  <div class="shell">
    <nav class="row-tight text-sm muted" style="margin-bottom:1.6rem" aria-label="Ruta">
      <a href="<?= url('/skills') ?>">Habilidades</a>
      <span class="faint">/</span>
      <span><?= e($skill['name']) ?></span>
    </nav>

    <div class="detail-grid">
      <div>
        <div class="row" style="align-items:flex-start;gap:1rem">
          <span class="card-glyph sky" style="width:52px;height:52px;border-radius:15px" aria-hidden="true">
            <?= icon('skill', 22) ?>
          </span>
          <div style="min-width:0;flex:1">
            <span class="eyebrow"><span class="dot"></span>Habilidad</span>
            <h1 style="font-size:clamp(1.8rem,4.2vw,2.8rem);margin-top:.8rem"><?= e($skill['name']) ?></h1>
          </div>
        </div>

        <p class="lede mt-2"><?= e($skill['short_description']) ?></p>

        <div class="chips mt-2">
          <?php if ($esDePago): ?><span class="badge badge-amber">De pago</span><?php endif; ?>
          <span class="badge badge-mint">v<?= e($skill['version']) ?></span>
          <?php if (!empty($skill['category_name'])): ?>
            <a class="badge" href="<?= url('/skills') ?>?category=<?= e($skill['category_slug']) ?>"><?= e($skill['category_name']) ?></a>
          <?php endif; ?>
          <?php foreach ($compat as $c): ?><span class="badge"><?= e($c) ?></span><?php endforeach; ?>
          <?php foreach ($tags as $t): ?>
            <a class="badge" href="<?= url('/skills') ?>?tag=<?= e($t) ?>">#<?= e($t) ?></a>
          <?php endforeach; ?>
        </div>

        <?php if ($skill['status'] !== 'published' || $skill['visibility'] === 'private'): ?>
          <div class="notice warn mt-2">
            <?= icon('eye-off', 17) ?>
            <span>
              Ficha no pública: estado <strong><?= e(App\Models\Skill::statusLabel((string) $skill['status'])) ?></strong>.
              La ves porque eres su autor o administrador.
            </span>
          </div>
        <?php endif; ?>

        <?php if ($esDePago && $canEdit): ?>
          <div class="notice warn mt-2">
            <?= icon('eye', 17) ?>
            <span>
              Es una plantilla de pago. Ves el contenido porque eres su autor o
              administrador; para cualquier otra persona sólo se muestra qué hace.
            </span>
          </div>
        <?php endif; ?>

        <?php if (!$verContenido): ?>
          <div class="shellbox mt-3">
            <div class="core pad-lg">
              <h2 style="font-size:1.15rem">Qué hace</h2>
              <?php if (!empty($skill['teaser'])): ?>
                <div class="prose mt-2"><?= \App\Core\Markdown::toHtml((string) $skill['teaser']) ?></div>
              <?php else: ?>
                <p class="muted mt-2"><?= e($skill['short_description']) ?></p>
              <?php endif; ?>
              <div class="notice mt-3">
                <?= icon('info', 17) ?>
                <span>
                  El contenido de esta habilidad no se previsualiza ni se descarga
                  desde el catálogo. Se entrega al contratarla.
                </span>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($verContenido): ?>
        <div class="shellbox mt-3" data-tabs>
          <div class="core">
            <div class="code-head">
              <span class="lights" aria-hidden="true"><i></i><i></i><i></i></span>
              <span class="name"><?= e($skill['slug']) ?></span>
              <span class="tabs" style="margin-left:auto">
                <button type="button" class="tab is-active" data-tab="doc">Contenido</button>
                <button type="button" class="tab" data-tab="json">.json</button>
                <button type="button" class="tab" data-tab="md">.md</button>
              </span>
            </div>

            <div data-panel="doc" class="pad-lg prose"><?= $html ?></div>

            <div data-panel="json" hidden>
              <div class="viewer"><pre id="skillJson"><?= e($jsonPreview) ?></pre></div>
              <div class="pad" style="border-top:1px solid var(--line)">
                <button type="button" class="btn btn-ghost btn-sm" data-copy-target="#skillJson"
                        data-copy-msg="JSON copiado">Copiar JSON <?= btnIcon('copy') ?></button>
              </div>
            </div>

            <div data-panel="md" hidden>
              <div class="viewer"><pre id="skillMd"><?= e(Packager::skillMarkdown($skill)) ?></pre></div>
              <div class="pad" style="border-top:1px solid var(--line)">
                <button type="button" class="btn btn-ghost btn-sm" data-copy-target="#skillMd"
                        data-copy-msg="SKILL.md copiado">Copiar SKILL.md <?= btnIcon('copy') ?></button>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <?php if ($agents): ?>
          <div class="mt-3">
            <h2 style="font-size:1.15rem;margin-bottom:1rem">Agentes que la incluyen</h2>
            <div class="stack-sm">
              <?php foreach ($agents as $a): ?>
                <a class="format-item" href="<?= url('/agents/' . $a['slug']) ?>">
                  <span class="ext"><?= icon('agent', 14) ?></span>
                  <span>
                    <span class="t"><?= e($a['name']) ?></span>
                    <span class="d"><?= e(Str::excerpt((string) $a['short_description'], 90)) ?></span>
                  </span>
                  <span class="go"><?= icon('arrow', 15) ?></span>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <?php if (count($versions) > 1): ?>
          <div class="shellbox mt-3">
            <div class="core pad">
              <h2 style="font-size:1.05rem;margin-bottom:.9rem">Historial de versiones</h2>
              <div class="meta-list">
                <?php foreach ($versions as $v): ?>
                  <div>
                    <span class="k">
                      <span class="mono">v<?= e($v['version']) ?></span>
                      <?php if (!empty($v['changelog'])): ?>
                        — <?= e(Str::excerpt((string) $v['changelog'], 60)) ?>
                      <?php endif; ?>
                    </span>
                    <span class="v"><?= e(date('d/m/Y', strtotime((string) $v['created_at']))) ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <aside class="side-stack">
        <?php if ($esDePago): ?>
          <?= View::partial('partials/paid-panel', ['row' => $skill, 'tipo' => 'habilidad']) ?>
        <?php endif; ?>

        <?php if ($verContenido): ?>
        <div class="shellbox tight">
          <div class="core pad">
            <h3 style="font-size:.95rem">Descargar</h3>
            <p class="text-sm muted mt-1" style="margin-bottom:1rem">Sin registro. Elige el formato.</p>

            <div class="format-list">
              <?php foreach ($available as $fmt):
                  if (!isset($formatInfo[$fmt])) { continue; }
                  [$ext, $title, $desc, $tone] = $formatInfo[$fmt]; ?>
                <a class="format-item" href="<?= url('/skills/' . $skill['slug'] . '/download') ?>?format=<?= e($fmt) ?>">
                  <span class="ext <?= e($tone) ?>"><?= e($ext) ?></span>
                  <span>
                    <span class="t"><?= e($title) ?></span>
                    <span class="d"><?= e($desc) ?></span>
                  </span>
                  <span class="go"><?= icon('download', 15) ?></span>
                </a>
              <?php endforeach; ?>
            </div>

            <div class="stack-sm mt-2">
              <?= View::partial('partials/account-button', [
                  'type'   => 'skill',
                  'id'     => (int) $skill['id'],
                  'active' => $isFavorite,
                  'name'   => (string) $skill['name'],
                  'size'   => '',
                  'block'  => true,
              ]) ?>
              <button type="button" class="btn btn-ghost btn-sm btn-block"
                      data-cart-toggle="skill" data-slug="<?= e($skill['slug']) ?>"
                      data-add-label="Añadir a un paquete" aria-pressed="false">
                <span data-cart-label>Añadir a un paquete</span> <?= btnIcon('cart') ?>
              </button>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <div class="shellbox tight">
          <div class="core pad">
            <div class="meta-list">
              <div><span class="k">Autor</span><span class="v"><?= e($skill['author_display']) ?></span></div>
              <div><span class="k">Versión</span><span class="v"><?= e($skill['version']) ?></span></div>
              <div><span class="k">Descargas</span><span class="v"><?= e(Str::compactNumber((int) $skill['downloads'])) ?></span></div>
              <div><span class="k">Actualizada</span><span class="v"><?= e(date('d/m/Y', strtotime((string) $skill['updated_at']))) ?></span></div>
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
                      data-share-title="<?= e($skill['name']) ?>">Compartir <?= btnIcon('share') ?></button>
            </div>
            <div class="btn-row mt-1">
              <a class="btn btn-ghost btn-sm" rel="noopener" target="_blank"
                 href="https://wa.me/?text=<?= rawurlencode($skill['name'] . ' — ' . $publicUrl) ?>">WhatsApp</a>
              <a class="btn btn-ghost btn-sm"
                 href="mailto:?subject=<?= rawurlencode((string) $skill['name']) ?>&body=<?= rawurlencode($publicUrl) ?>">Correo</a>
            </div>
          </div>
        </div>

        <?php if ($canEdit): ?>
          <div class="shellbox tight">
            <div class="core pad">
              <h3 style="font-size:.95rem">Gestión</h3>
              <a class="btn btn-ghost btn-sm mt-1" href="<?= url('/dashboard/skills/' . $skill['id'] . '/edit') ?>">
                Editar <?= btnIcon('edit') ?>
              </a>
            </div>
          </div>
        <?php endif; ?>
      </aside>
    </div>

    <?php if ($related): ?>
      <div style="margin-top:clamp(3rem,7vw,5rem)">
        <h2 style="font-size:1.3rem;margin-bottom:1.4rem">También te puede servir</h2>
        <div class="grid">
          <?php foreach ($related as $i => $r): ?>
            <?= View::partial('partials/skill-card', ['skill' => $r, 'delay' => $i + 1]) ?>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<?= View::partial('partials/cart-bar') ?>
