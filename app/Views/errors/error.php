<?php /** @var int $code @var string $title @var string $message */ ?>
<section class="section">
  <div class="shell shell-narrow center">
    <span class="eyebrow"><span class="dot"></span>Error <?= (int) $code ?></span>
    <h1 class="mt-2"><?= e($title) ?></h1>
    <p class="lede mt-2"><?= e($message) ?></p>
    <div class="btn-row mt-3" style="justify-content:center">
      <a class="btn btn-primary" href="<?= url('/') ?>">Volver al inicio <?= btnIcon('arrow') ?></a>
      <a class="btn btn-ghost" href="<?= url('/agents') ?>">Ver la tienda</a>
    </div>
  </div>
</section>
