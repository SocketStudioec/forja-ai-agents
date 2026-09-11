<?php /** @var array $categories */ ?>
<section class="section-sm">
  <div class="shell">
    <div class="section-head">
      <span class="eyebrow"><span class="dot"></span>Índice</span>
      <h1 style="font-size:clamp(2rem,4.6vw,3.2rem);margin-top:1.2rem">Categorías</h1>
      <p class="lede mt-2">Recorre la tienda por el tipo de trabajo que necesitas resolver.</p>
    </div>

    <?php if ($categories): ?>
      <div class="grid">
        <?php foreach ($categories as $i => $c):
            $total = (int) $c['skills_count'] + (int) $c['agents_count']; ?>
          <article class="shellbox card reveal" data-d="<?= min(4, ($i % 4) + 1) ?>">
            <div class="core">
              <div class="card-top">
                <span class="card-glyph" aria-hidden="true"><?= icon('tag', 18) ?></span>
                <div style="min-width:0">
                  <a class="card-title" href="<?= url('/agents') ?>?category=<?= e($c['slug']) ?>"><?= e($c['name']) ?></a>
                  <span class="card-role"><?= $total ?> recurso<?= $total === 1 ? '' : 's' ?></span>
                </div>
              </div>
              <?php if (!empty($c['description'])): ?>
                <p class="card-desc"><?= e($c['description']) ?></p>
              <?php endif; ?>
              <div class="card-tags chips">
                <span class="badge badge-mint"><?= (int) $c['agents_count'] ?> agentes</span>
                <span class="badge badge-sky"><?= (int) $c['skills_count'] ?> skills</span>
              </div>
              <div class="card-foot">
                <a class="btn btn-sm btn-ghost" href="<?= url('/agents') ?>?category=<?= e($c['slug']) ?>">Agentes</a>
                <a class="btn btn-sm btn-ghost push" href="<?= url('/skills') ?>?category=<?= e($c['slug']) ?>">Habilidades</a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="shellbox">
        <div class="core empty">
          <span class="glyph"><?= icon('tag', 22) ?></span>
          <h3>Aún no hay categorías</h3>
          <p>Un administrador puede crearlas desde el panel.</p>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>
