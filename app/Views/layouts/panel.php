<?php
/** Panel de usuario y de administración: navegación lateral persistente. */
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Session;
use App\Models\Submission;

$pageTitle = ($__title !== '' ? $__title . ' · ' : '') . Config::appName();
$path      = (string) Session::get('__path', '/');
$isAdminUi = strncmp($path, '/admin', 6) === 0;

$pending = $isAdmin ? Submission::pendingCount() : 0;

$userLinks = [
    ['/dashboard',            'Resumen',      'grid'],
    ['/dashboard/agents',     'Mis agentes',  'agent'],
    ['/dashboard/skills',     'Mis skills',   'skill'],
    ['/dashboard/favorites',  'Favoritos',    'star'],
    ['/dashboard/account',    'Mi cuenta',    'settings'],
];
$adminLinks = [
    ['/admin',              'Resumen',      'chart',    null],
    ['/admin/submissions',  'Bandeja',      'inbox',    $pending ?: null],
    ['/admin/agents',       'Agentes',      'agent',    null],
    ['/admin/skills',       'Skills',       'skill',    null],
    ['/admin/users',        'Usuarios',     'users',    null],
    ['/admin/categories',   'Categorías',   'tag',      null],
    ['/admin/downloads',    'Descargas',    'download', null],
    ['/admin/audit',        'Auditoría',    'shield',   null],
];
?><!doctype html>
<html lang="es" data-base="<?= e(Config::basePath()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($pageTitle) ?></title>
<meta name="robots" content="noindex">
<meta name="theme-color" content="#080D22" media="(prefers-color-scheme: dark)">
<meta name="theme-color" content="#F5F7FD" media="(prefers-color-scheme: light)">
<link rel="icon" href="<?= asset('favicon.svg') ?>" type="image/svg+xml">
<link rel="preload" as="font" type="font/woff2" crossorigin href="<?= asset('fonts/jakarta-latin.woff2') ?>">
<link rel="preload" as="font" type="font/woff2" crossorigin href="<?= asset('fonts/jbmono-latin.woff2') ?>">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<script src="<?= asset('js/theme.js') ?>"></script>
</head>
<body>
<a class="skip-link" href="#main">Saltar al contenido</a>

<div class="nav-wrap">
  <nav class="nav" id="nav" aria-label="Principal">
    <a class="brand" href="<?= url('/') ?>">
      <span class="brand-mark" aria-hidden="true"><?= e(mb_strtoupper(mb_substr(Config::appName(), 0, 1))) ?></span>
      <span class="brand-name"><?= e(Config::appName()) ?> <span class="hide-sm">/ <?= $isAdminUi ? 'admin' : 'panel' ?></span></span>
    </a>

    <div class="nav-links">
      <a class="nav-link" href="<?= url('/agents') ?>">Ver tienda</a>
      <?php if ($isAdmin && !$isAdminUi): ?>
        <a class="nav-link" href="<?= url('/admin') ?>">Administración</a>
      <?php elseif ($isAdminUi): ?>
        <a class="nav-link" href="<?= url('/dashboard') ?>">Mi panel</a>
      <?php endif; ?>
    </div>

    <div class="nav-actions">
      <button type="button" class="icon-btn" id="themeToggle" aria-label="Cambiar entre tema claro y oscuro">
        <span class="t-dark"><?= icon('moon') ?></span>
        <span class="t-light" hidden><?= icon('sun') ?></span>
      </button>
      <span class="badge hide-sm" title="<?= e($authUser['email'] ?? '') ?>">
        <?= e($authUser['username'] ?? '') ?>
      </span>
      <form method="post" action="<?= url('/logout') ?>" style="display:inline">
        <?= Csrf::field() ?>
        <button type="submit" class="icon-btn" aria-label="Cerrar sesión"><?= icon('logout') ?></button>
      </form>
    </div>
  </nav>
</div>

<main id="main">
  <div class="shell panel-shell">
    <aside>
      <nav class="side-nav" aria-label="Secciones del panel">
        <?php if ($isAdminUi): ?>
          <span class="grp">Administración</span>
          <?php foreach ($adminLinks as [$href, $label, $ico, $count]): ?>
            <a class="<?= $path === $href || ($href !== '/admin' && strncmp($path, $href, strlen($href)) === 0) ? 'is-active' : '' ?>"
               href="<?= url($href) ?>">
              <?= icon($ico, 15) ?><?= e($label) ?>
              <?php if ($count): ?><span class="count"><?= (int) $count ?></span><?php endif; ?>
            </a>
          <?php endforeach; ?>
        <?php else: ?>
          <span class="grp">Mi espacio</span>
          <?php foreach ($userLinks as [$href, $label, $ico]): ?>
            <a class="<?= $path === $href || ($href !== '/dashboard' && strncmp($path, $href, strlen($href)) === 0) ? 'is-active' : '' ?>"
               href="<?= url($href) ?>">
              <?= icon($ico, 15) ?><?= e($label) ?>
            </a>
          <?php endforeach; ?>
          <?php if ($isAdmin): ?>
            <span class="grp">Administración</span>
            <a href="<?= url('/admin') ?>"><?= icon('shield', 15) ?>Ir al panel admin</a>
          <?php endif; ?>
        <?php endif; ?>
      </nav>
    </aside>

    <div><?= $content ?></div>
  </div>
</main>

<?= App\Core\View::partial('partials/flashes', ['flashes' => $flashes ?? []]) ?>
<script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
