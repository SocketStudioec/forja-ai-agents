<?php
/** @var array $result @var array $filters @var array $categories @var array $compat */
use App\Core\View;

$sorts = [
    'recent'    => 'Más recientes',
    'downloads' => 'Más descargadas',
    'featured'  => 'Destacadas',
    'name'      => 'Alfabético',
];
?>
<section class="section-sm">
  <div class="shell">
    <div class="section-head">
      <span class="eyebrow"><span class="dot"></span>Biblioteca</span>
      <h1 style="font-size:clamp(2rem,4.6vw,3.2rem);margin-top:1.2rem">Habilidades</h1>
      <p class="lede mt-2">
        Cada habilidad se descarga como <span class="mono">.json</span> para el agente y como
        <span class="mono">.md</span> para leerla. Añade las que quieras a tu paquete.
      </p>
    </div>

    <form class="toolbar" method="get" action="<?= url('/skills') ?>" role="search">
      <label class="search">
        <span class="sr-only">Buscar habilidades</span>
        <?= icon('search') ?>
        <input class="input" type="search" name="q" value="<?= e($filters['q']) ?>"
               placeholder="Buscar por nombre, descripción o etiqueta…">
      </label>

      <label class="filter-select">
        <span class="sr-only">Categoría</span>
        <select class="select" name="category" data-autosubmit>
          <option value="">Todas las categorías</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= e($c['slug']) ?>" <?= $filters['category'] === $c['slug'] ? 'selected' : '' ?>>
              <?= e($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <label class="filter-select">
        <span class="sr-only">Compatibilidad</span>
        <select class="select" name="compat" data-autosubmit>
          <option value="">Cualquier agente</option>
          <?php foreach ($compat as $c): ?>
            <option value="<?= e($c) ?>" <?= $filters['compat'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <label class="filter-select">
        <span class="sr-only">Tipo</span>
        <select class="select" name="tier" data-autosubmit>
          <option value="">Gratis y de pago</option>
          <option value="free" <?= ($filters['tier'] ?? '') === 'free' ? 'selected' : '' ?>>Sólo gratuitas</option>
          <option value="paid" <?= ($filters['tier'] ?? '') === 'paid' ? 'selected' : '' ?>>Sólo de pago</option>
        </select>
      </label>

      <label class="filter-select">
        <span class="sr-only">Ordenar</span>
        <select class="select" name="sort" data-autosubmit>
          <?php foreach ($sorts as $k => $label): ?>
            <option value="<?= e($k) ?>" <?= $filters['sort'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <button type="submit" class="btn btn-primary btn-sm">Filtrar <?= btnIcon('filter') ?></button>
    </form>

    <p class="text-sm muted mt-2">
      <?= (int) $result['total'] ?> habilidad<?= $result['total'] === 1 ? '' : 'es' ?>
      <?php if ($filters['q'] !== ''): ?> para «<?= e($filters['q']) ?>»<?php endif; ?>
      <?php if ($filters['tag'] !== ''): ?> con la etiqueta #<?= e($filters['tag']) ?><?php endif; ?>
    </p>
  </div>
</section>

<section style="padding-bottom:clamp(4rem,9vw,7rem)">
  <div class="shell">
    <?php if ($result['items']): ?>
      <div class="grid">
        <?php foreach ($result['items'] as $i => $skill): ?>
          <?= View::partial('partials/skill-card', ['skill' => $skill, 'delay' => min(4, ($i % 4) + 1)]) ?>
        <?php endforeach; ?>
      </div>

      <?= View::partial('partials/pager', [
          'page' => $result['page'], 'pages' => $result['pages'], 'path' => '/skills',
      ]) ?>
    <?php else: ?>
      <div class="shellbox">
        <div class="core empty">
          <span class="glyph"><?= icon('search', 22) ?></span>
          <h3>Ninguna habilidad coincide</h3>
          <p>Prueba con otras palabras, quita un filtro o envía la tuya.</p>
          <div class="btn-row mt-1" style="justify-content:center">
            <a class="btn btn-ghost btn-sm" href="<?= url('/skills') ?>">Limpiar filtros</a>
            <a class="btn btn-primary btn-sm" href="<?= url('/submit') ?>">Enviar una skill <?= btnIcon('arrow-up-right') ?></a>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<?= View::partial('partials/cart-bar') ?>
