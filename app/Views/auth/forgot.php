<?php /** @var array $errors */ use App\Core\Csrf; ?>
<div class="shellbox">
  <div class="core pad-lg">
    <span class="eyebrow"><span class="dot"></span>Recuperar</span>
    <h1 style="font-size:1.7rem;margin-top:1.1rem">¿Olvidaste la contraseña?</h1>
    <p class="text-sm muted mt-1">
      Escribe tu correo y te enviamos un enlace para crear una nueva. Caduca en una hora.
    </p>

    <form method="post" action="<?= url('/forgot') ?>" class="mt-3" novalidate>
      <?= Csrf::field() ?>
      <div class="field<?= isset($errors['email']) ? ' has-error' : '' ?>">
        <label class="label" for="email">Correo</label>
        <input class="input" type="email" id="email" name="email" required autocomplete="email"
               value="<?= e(old('email')) ?>" placeholder="tu@email.com">
        <?php if (isset($errors['email'])): ?><p class="error-text"><?= e($errors['email']) ?></p><?php endif; ?>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Enviar enlace <?= btnIcon('mail') ?></button>
    </form>

    <p class="text-sm muted center mt-3">
      <a href="<?= url('/login') ?>" style="color:var(--mint);font-weight:600">Volver a entrar</a>
    </p>
  </div>
</div>
