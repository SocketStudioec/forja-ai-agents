<?php
/** @var array $result @var array $filters @var array $categories @var array $compat */
use App\Core\View;

$sorts = [
    'recent'    => 'Más recientes',
    'downloads' => 'Más descargados',
    'skills'    => 'Con más habilidades',
    'name'      => 'Alfabético',
];
?>
<section class="section-sm">
  <div class="shell">
    <div class="section-head">
      <span class="eyebrow"><span class="dot"></span>Tienda</span>
      <h1 style="font-size:clamp(2rem,4.6vw,3.2rem);margin-top:1.2rem">Agentes</h1>
      <p class="lede mt-2">
        Cada agente trae sus reglas en un archivo Markdown y sus habilidades como archivos JSON
        independientes. Elige, quita lo que no uses y descarga.
      </p>
    </div>

    <form class="toolbar" method="get" action="<?= url('/agents') ?>" role="search">
      <label class="search">
        <span class="sr-only">Buscar agentes</span>
        <?= icon('search') ?>
        <input class="input" type="search" name="q" value="<?= e($filters['q']) ?>"
               placeholder="Buscar por nombre, rol o etiqueta…">
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
      <?= (int) $result['total'] ?> agente<?= $result['total'] === 1 ? '' : 's' ?>
      <?php if ($filters['q'] !== ''): ?> para «<?= e($filters['q']) ?>»<?php endif; ?>
    </p>
  </div>
</section>

<section style="padding-bottom:clamp(4rem,9vw,7rem)">
  <div class="shell">
    <?php if ($result['items']): ?>
      <div class="grid">
        <?php foreach ($result['items'] as $i => $agent): ?>
          <?= View::partial('partials/agent-card', ['agent' => $agent, 'delay' => min(4, ($i % 4) + 1)]) ?>
        <?php endforeach; ?>
      </div>

      <?= View::partial('partials/pager', [
          'page' => $result['page'], 'pages' => $result['pages'], 'path' => '/agents',
      ]) ?>
    <?php else: ?>
      <div class="shellbox">
        <div class="core empty">
          <span class="glyph"><?= icon('search', 22) ?></span>
          <h3>Ningún agente coincide</h3>
          <p>Prueba con otras palabras o quita algún filtro.</p>
          <a class="btn btn-ghost btn-sm mt-1" href="<?= url('/agents') ?>">Limpiar filtros</a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<?= View::partial('partials/cart-bar') ?>
