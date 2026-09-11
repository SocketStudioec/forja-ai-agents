<?php
/** @var array|null $authUser @var bool $isAdmin */
use App\Core\Config;

$links = [
    ['/agents',       'Agentes'],
    ['/skills',       'Habilidades'],
    ['/builder',      'Constructor'],
    ['/categories',   'Categorías'],
    ['/docs/openclaw','Documentación'],
];
$initial = mb_substr(Config::appName(), 0, 1);
?>
<div class="nav-wrap">
  <nav class="nav" id="nav" aria-label="Principal">
    <a class="brand" href="<?= url('/') ?>">
      <span class="brand-mark" aria-hidden="true"><?= e(mb_strtoupper($initial)) ?></span>
      <span class="brand-name"><?= e(Config::appName()) ?> <span class="hide-sm">/ agentes</span></span>
    </a>

    <div class="nav-links">
      <?php foreach ($links as [$href, $label]): ?>
        <a class="nav-link<?= activeClass($href) ?>" href="<?= url($href) ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </div>

    <div class="nav-actions">
      <button type="button" class="icon-btn" id="themeToggle" aria-label="Cambiar entre tema claro y oscuro">
        <span class="t-dark"><?= icon('moon') ?></span>
        <span class="t-light" hidden><?= icon('sun') ?></span>
      </button>

      <?php if ($authUser): ?>
        <a class="btn btn-ghost btn-sm" href="<?= url($isAdmin ? '/admin' : '/dashboard') ?>">
          <?= $isAdmin ? 'Admin' : 'Mi panel' ?>
        </a>
        <a class="btn btn-primary btn-sm" href="<?= url('/dashboard/agents/new') ?>">
          Crear agente <?= btnIcon('plus') ?>
        </a>
      <?php else: ?>
        <a class="btn btn-ghost btn-sm" href="<?= url('/login') ?>">Entrar</a>
        <a class="btn btn-primary btn-sm" href="<?= url('/submit') ?>">
          Enviar skill <?= btnIcon('arrow-up-right') ?>
        </a>
      <?php endif; ?>

      <button type="button" class="burger" id="burger" aria-expanded="false" aria-controls="navPanel" aria-label="Abrir menú">
        <i></i><i></i>
      </button>
    </div>
  </nav>
</div>

<div class="nav-panel" id="navPanel">
  <?php foreach ($links as [$href, $label]): ?>
    <a href="<?= url($href) ?>"><?= e($label) ?> <?= icon('arrow-up-right', 15) ?></a>
  <?php endforeach; ?>
  <?php if ($authUser): ?>
    <a href="<?= url($isAdmin ? '/admin' : '/dashboard') ?>"><?= $isAdmin ? 'Administración' : 'Mi panel' ?> <?= icon('arrow-up-right', 15) ?></a>
  <?php else: ?>
    <a href="<?= url('/login') ?>">Entrar <?= icon('arrow-up-right', 15) ?></a>
  <?php endif; ?>
  <div class="panel-cta">
    <a class="btn btn-primary btn-block" href="<?= url('/submit') ?>">Enviar una skill <?= btnIcon('arrow-up-right') ?></a>
  </div>
</div>
