<?php
/** @var array $agents @var array $skills */
use App\Core\Str;
use App\Core\View;
?>
<section class="section-sm">
  <div class="shell">
    <div class="section-head">
      <span class="eyebrow"><span class="dot"></span>Constructor</span>
      <h1 style="font-size:clamp(2rem,4.6vw,3.2rem);margin-top:1.2rem">Arma tu paquete</h1>
      <p class="lede mt-2">
        Marca los agentes y las habilidades que quieras. Te llevas un solo ZIP con las reglas
        en Markdown, cada habilidad en su archivo JSON y un índice que las enlaza.
      </p>
      <p class="text-sm muted mt-2">
        Llevas <strong data-cart-total>0</strong> elemento(s) seleccionados. La selección se guarda
        en este navegador mientras navegas por la tienda.
      </p>
    </div>

    <!-- ------------------------------------------------------------ Agentes -->
    <div class="row" style="align-items:flex-end;margin:2.5rem 0 1.2rem">
      <h2 style="font-size:1.35rem">Agentes</h2>
      <span class="badge push"><?= count($agents) ?> disponibles</span>
    </div>

    <?php if ($agents): ?>
      <div class="grid-2">
        <?php foreach ($agents as $i => $a): ?>
          <article class="shellbox tight reveal" data-d="<?= min(4, ($i % 4) + 1) ?>">
            <div class="core pad">
              <div class="row" style="align-items:flex-start">
                <span class="card-glyph" aria-hidden="true"><?= e(glyphFor((string) $a['name'])) ?></span>
                <div style="min-width:0;flex:1">
                  <a class="card-title" href="<?= url('/agents/' . $a['slug']) ?>"><?= e($a['name']) ?></a>
                  <?php if (!empty($a['role_title'])): ?>
                    <span class="card-role"><?= e($a['role_title']) ?></span>
                  <?php endif; ?>
                </div>
                <span class="badge mono nowrap">v<?= e($a['version']) ?></span>
              </div>

              <p class="text-sm muted mt-2"><?= e(Str::excerpt((string) $a['short_description'], 120)) ?></p>

              <?php if (!empty($a['skill_list'])): ?>
                <div class="skill-strip" style="padding:0;margin-top:.9rem">
                  <?php foreach (array_slice($a['skill_list'], 0, 5) as $s): ?>
                    <span class="skill-pill"><?= e(Str::excerpt((string) $s['name'], 20)) ?><span class="ext">.json</span></span>
                  <?php endforeach; ?>
                  <?php if (count($a['skill_list']) > 5): ?>
                    <span class="skill-pill">+<?= count($a['skill_list']) - 5 ?></span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>

              <div class="btn-row mt-2">
                <button type="button" class="btn btn-ghost btn-sm" data-cart-toggle="agent"
                        data-slug="<?= e($a['slug']) ?>" data-add-label="Añadir" aria-pressed="false">
                  <span data-cart-label>Añadir</span> <?= btnIcon('cart') ?>
                </button>
                <a class="btn btn-ghost btn-sm" href="<?= url('/agents/' . $a['slug']) ?>">Ver ficha</a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="shellbox">
        <div class="core empty">
          <span class="glyph"><?= icon('agent', 22) ?></span>
          <h3>Sin agentes publicados</h3>
          <p>En cuanto haya agentes en la tienda podrás combinarlos aquí.</p>
        </div>
      </div>
    <?php endif; ?>

    <!-- -------------------------------------------------------- Habilidades -->
    <div class="row" style="align-items:flex-end;margin:3rem 0 1.2rem">
      <h2 style="font-size:1.35rem">Habilidades sueltas</h2>
      <span class="badge push"><?= count($skills) ?> disponibles</span>
    </div>

    <?php if ($skills): ?>
      <div class="shellbox">
        <div class="core pad stack-sm">
          <?php foreach ($skills as $s): ?>
            <div class="format-item">
              <span class="ext sky">.json</span>
              <span style="min-width:0;flex:1">
                <span class="t"><?= e($s['name']) ?> <span class="mono faint" style="font-size:.72rem">v<?= e($s['version']) ?></span></span>
                <span class="d"><?= e(Str::excerpt((string) $s['short_description'], 110)) ?></span>
              </span>
              <button type="button" class="icon-btn" data-cart-toggle="skill" data-slug="<?= e($s['slug']) ?>"
                      aria-pressed="false" aria-label="Añadir <?= e($s['name']) ?> a mi paquete">
                <?= icon('cart', 15) ?>
              </button>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php else: ?>
      <div class="shellbox">
        <div class="core empty">
          <span class="glyph"><?= icon('skill', 22) ?></span>
          <h3>Sin habilidades publicadas</h3>
          <p>Envía la primera y la revisamos.</p>
          <a class="btn btn-primary btn-sm mt-1" href="<?= url('/submit') ?>">Enviar una skill <?= btnIcon('arrow-up-right') ?></a>
        </div>
      </div>
    <?php endif; ?>

    <div style="height:6rem"></div>
  </div>
</section>

<?= View::partial('partials/cart-bar') ?>
