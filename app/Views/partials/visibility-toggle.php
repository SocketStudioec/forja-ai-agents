<?php
/**
 * Botón para alternar entre pública y privada de un tirón.
 * Envía sólo `visibility`, así que nunca toca el estado editorial.
 *
 * @var string $action   ruta del formulario
 * @var string $current  visibilidad actual
 * @var string $label    texto del recurso, para la etiqueta accesible
 * @var bool   $compact  true = sólo icono
 */
use App\Core\Csrf;
use App\Core\Visibility;

$next    = Visibility::toggle($current);
$compact = $compact ?? false;
$esPub   = $current === Visibility::PUBLIC_;
$titulo  = $esPub ? 'Hacer privada' : 'Hacer pública';
?>
<form method="post" action="<?= e($action) ?>" style="display:inline">
  <?= Csrf::field() ?>
  <input type="hidden" name="visibility" value="<?= e($next) ?>">
  <?php if ($compact): ?>
    <button type="submit" class="icon-btn <?= $esPub ? 'is-on' : '' ?>"
            title="<?= e($titulo) ?>"
            aria-label="<?= e($titulo . ': ' . ($label ?? '')) ?>">
      <?= icon($esPub ? 'eye' : 'eye-off', 15) ?>
    </button>
  <?php else: ?>
    <button type="submit" class="btn btn-ghost btn-sm">
      <?= e($titulo) ?>
    </button>
  <?php endif; ?>
</form>
