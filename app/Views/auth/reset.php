<?php /** @var string $token @var array $errors */ use App\Core\Csrf; ?>
<div class="shellbox">
  <div class="core pad-lg">
    <span class="eyebrow"><span class="dot"></span>Nueva contraseña</span>
    <h1 style="font-size:1.7rem;margin-top:1.1rem">Elige una contraseña</h1>
    <p class="text-sm muted mt-1">Mínimo 10 caracteres, combinando letras y números.</p>

    <form method="post" action="<?= url('/reset/' . $token) ?>" class="mt-3" novalidate>
      <?= Csrf::field() ?>
      <div class="field<?= isset($errors['password']) ? ' has-error' : '' ?>">
        <label class="label" for="password">Contraseña nueva</label>
        <input class="input" type="password" id="password" name="password" required autocomplete="new-password">
        <?php if (isset($errors['password'])): ?><p class="error-text"><?= e($errors['password']) ?></p><?php endif; ?>
      </div>
      <div class="field<?= isset($errors['password_confirm']) ? ' has-error' : '' ?>">
        <label class="label" for="password_confirm">Repetir contraseña</label>
        <input class="input" type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password">
        <?php if (isset($errors['password_confirm'])): ?><p class="error-text"><?= e($errors['password_confirm']) ?></p><?php endif; ?>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Guardar contraseña <?= btnIcon('check') ?></button>
    </form>
  </div>
</div>
