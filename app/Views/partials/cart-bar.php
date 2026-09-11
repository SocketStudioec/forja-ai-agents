<?php use App\Core\Csrf; ?>
<form class="cart-bar" id="cartBar" method="post" action="<?= url('/builder/download') ?>">
  <?= Csrf::field() ?>
  <input type="hidden" name="agents" value="">
  <input type="hidden" name="skills" value="">

  <span class="n" data-cart-count>0</span>
  <span class="t">
    Tu paquete
    <span data-cart-detail>0 agentes · 0 habilidades</span>
  </span>

  <span class="btn-row">
    <button type="button" class="btn btn-ghost btn-sm" id="cartClear">Vaciar</button>
    <button type="submit" class="btn btn-primary btn-sm">
      Descargar ZIP <?= btnIcon('download') ?>
    </button>
  </span>
</form>
