<?php
/**
 * «Agregar a mi cuenta»: guarda el agente o la habilidad en la biblioteca
 * personal de quien ha iniciado sesión. A quien no tiene sesión lo lleva al
 * acceso conservando a dónde quería ir.
 *
 * @var string $type   'agent' | 'skill'
 * @var int    $id
 * @var bool   $active ya está en su cuenta
 * @var string $name   para la etiqueta accesible
 * @var string $size   'sm' | ''
 * @var bool   $block  ocupar todo el ancho
 */
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;

$size   = $size ?? 'sm';
$block  = $block ?? false;
$active = $active ?? false;
$clases = 'btn ' . ($active ? 'btn-ghost' : 'btn-primary')
        . ($size === 'sm' ? ' btn-sm' : '')
        . ($block ? ' btn-block' : '');
?>
<?php if (Auth::check()): ?>
  <button type="button" class="<?= e($clases) ?>"
          data-fav="<?= e($type) ?>"
          data-fav-id="<?= (int) $id ?>"
          data-token="<?= e(Csrf::token()) ?>"
          data-label-on="En mi cuenta"
          data-label-off="Agregar a mi cuenta"
          aria-pressed="<?= $active ? 'true' : 'false' ?>">
    <span data-fav-label><?= $active ? 'En mi cuenta' : 'Agregar a mi cuenta' ?></span>
    <?= btnIcon($active ? 'check' : 'plus') ?>
  </button>
<?php else: ?>
  <a class="<?= e($clases) ?>"
     href="<?= url('/login') ?>"
     title="Inicia sesión para guardarlo en tu cuenta"
     aria-label="Inicia sesión para agregar <?= e($name ?? '') ?> a tu cuenta">
    Agregar a mi cuenta <?= btnIcon('plus') ?>
  </a>
<?php endif; ?>
