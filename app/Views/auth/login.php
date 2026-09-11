<?php /** @var array $errors */ use App\Core\Csrf; ?>
<div class="shellbox">
  <div class="core pad-lg">
    <span class="eyebrow"><span class="dot"></span>Acceso</span>
    <h1 style="font-size:1.7rem;margin-top:1.1rem">Entrar</h1>
    <p class="text-sm muted mt-1">Para publicar agentes y habilidades con tu nombre.</p>

    <?php if (isset($errors['login'])): ?>
      <div class="notice danger mt-2"><?= icon('alert', 17) ?><span><?= e($errors['login']) ?></span></div>
    <?php endif; ?>

    <form method="post" action="<?= url('/login') ?>" class="mt-3" novalidate>
      <?= Csrf::field() ?>

      <div class="field">
        <label class="label" for="login">Correo o usuario</label>
        <input class="input" type="text" id="login" name="login" required autocomplete="username"
               value="<?= e(old('login')) ?>" placeholder="tu@email.com">
      </div>

      <div class="field">
        <label class="label" for="password">Contraseña</label>
        <input class="input" type="password" id="password" name="password" required autocomplete="current-password">
      </div>

      <button type="submit" class="btn btn-primary btn-block">Entrar <?= btnIcon('arrow') ?></button>
    </form>

    <div class="row mt-3 text-sm">
      <a href="<?= url('/forgot') ?>" class="muted">¿Olvidaste la contraseña?</a>
      <a href="<?= url('/register') ?>" class="push" style="color:var(--mint);font-weight:600">Crear cuenta</a>
    </div>
  </div>
</div>

<p class="text-sm muted center mt-2">
  ¿Sólo quieres compartir una skill? <a href="<?= url('/submit') ?>" style="color:var(--sky)">Envíala sin cuenta</a>.
</p>
