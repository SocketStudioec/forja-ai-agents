<?php
/** Pantallas de acceso: sin navegación completa, foco total en el formulario. */
use App\Core\Config;

$pageTitle = ($__title !== '' ? $__title . ' · ' : '') . Config::appName();
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
<main id="main">
  <div class="shell" style="max-width:480px">
    <div style="padding-block:clamp(2.5rem,8vw,4.5rem) 3rem">
      <a class="brand" href="<?= url('/') ?>" style="margin-bottom:2.2rem">
        <span class="brand-mark" aria-hidden="true"><?= e(mb_strtoupper(mb_substr(Config::appName(), 0, 1))) ?></span>
        <span class="brand-name"><?= e(Config::appName()) ?></span>
      </a>
      <?= $content ?>
    </div>
  </div>
</main>

<?= App\Core\View::partial('partials/flashes', ['flashes' => $flashes ?? []]) ?>
<script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
