<?php
/**
 * Estructura común de las páginas públicas.
 * @var string $content
 * @var string $__title
 * @var array  $flashes
 * @var array|null $authUser
 */
use App\Core\Config;

$pageTitle = ($__title !== '' ? $__title . ' · ' : '') . Config::appName();
$metaDesc  = $metaDesc ?? 'Tienda de agentes de IA: reglas en Markdown, habilidades en JSON. Explora, arma tu paquete y descárgalo listo para usar.';
?><!doctype html>
<html lang="es" data-base="<?= e(Config::basePath()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($metaDesc) ?>">
<meta name="theme-color" content="#080D22" media="(prefers-color-scheme: dark)">
<meta name="theme-color" content="#F5F7FD" media="(prefers-color-scheme: light)">
<link rel="canonical" href="<?= e(Config::absUrl(App\Core\Session::get('__path', '/'))) ?>">

<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e(Config::appName()) ?>">
<meta property="og:title" content="<?= e($__title !== '' ? $__title : Config::appName()) ?>">
<meta property="og:description" content="<?= e($metaDesc) ?>">
<meta property="og:url" content="<?= e(Config::absUrl(App\Core\Session::get('__path', '/'))) ?>">
<meta name="twitter:card" content="summary">

<link rel="icon" href="<?= asset('favicon.svg') ?>" type="image/svg+xml">
<link rel="preload" as="font" type="font/woff2" crossorigin href="<?= e(Config::basePath()) ?>/assets/fonts/jakarta-latin.woff2">
<link rel="preload" as="font" type="font/woff2" crossorigin href="<?= e(Config::basePath()) ?>/assets/fonts/jbmono-latin.woff2">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<script src="<?= asset('js/theme.js') ?>"></script>
</head>
<body>
<a class="skip-link" href="#main">Saltar al contenido</a>

<?= App\Core\View::partial('partials/nav', ['authUser' => $authUser ?? null, 'isAdmin' => $isAdmin ?? false]) ?>

<main id="main"><?= $content ?></main>

<?= App\Core\View::partial('partials/footer') ?>
<?= App\Core\View::partial('partials/flashes', ['flashes' => $flashes ?? []]) ?>

<script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
