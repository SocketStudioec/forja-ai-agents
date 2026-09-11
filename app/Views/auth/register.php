<?php /** @var array $errors */ use App\Core\Csrf;
$err = static fn (string $k): string => isset($errors[$k]) ? ' has-error' : ''; ?>
<div class="shellbox">
  <div class="core pad-lg">
    <span class="eyebrow"><span class="dot"></span>Nueva cuenta</span>
    <h1 style="font-size:1.7rem;margin-top:1.1rem">Crear cuenta</h1>
    <p class="text-sm muted mt-1">Publica tus agentes, versiónalos y mira cuánto se descargan.</p>

    <?php if (isset($errors['general'])): ?>
      <div class="notice danger mt-2"><?= icon('alert', 17) ?><span><?= e($errors['general']) ?></span></div>
    <?php endif; ?>

    <form method="post" action="<?= url('/register') ?>" class="mt-3" novalidate>
      <?= Csrf::field() ?>
      <div style="position:absolute;left:-9999px" aria-hidden="true">
        <label>No rellenar<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
      </div>

      <div class="form-grid">
        <div class="field<?= $err('name') ?>">
          <label class="label" for="name">Nombre <span class="req">*</span></label>
          <input class="input" type="text" id="name" name="name" required maxlength="80"
                 value="<?= e(old('name')) ?>" autocomplete="given-name">
          <?php if (isset($errors['name'])): ?><p class="error-text"><?= e($errors['name']) ?></p><?php endif; ?>
        </div>
        <div class="field<?= $err('lastname') ?>">
          <label class="label" for="lastname">Apellido</label>
          <input class="input" type="text" id="lastname" name="lastname" maxlength="80"
                 value="<?= e(old('lastname')) ?>" autocomplete="family-name">
        </div>
      </div>

      <div class="field<?= $err('username') ?>">
        <label class="label" for="username">Usuario <span class="req">*</span></label>
        <input class="input mono" type="text" id="username" name="username" required maxlength="40"
               value="<?= e(old('username')) ?>" autocomplete="username" placeholder="tu-usuario">
        <p class="hint">Minúsculas, números, punto, guion y guion bajo. Es el nombre que verán los demás.</p>
        <?php if (isset($errors['username'])): ?><p class="error-text"><?= e($errors['username']) ?></p><?php endif; ?>
      </div>

      <div class="field<?= $err('email') ?>">
        <label class="label" for="email">Correo <span class="req">*</span></label>
        <input class="input" type="email" id="email" name="email" required maxlength="190"
               value="<?= e(old('email')) ?>" autocomplete="email">
        <?php if (isset($errors['email'])): ?><p class="error-text"><?= e($errors['email']) ?></p><?php endif; ?>
      </div>

      <div class="form-grid">
        <div class="field<?= $err('password') ?>">
          <label class="label" for="password">Contraseña <span class="req">*</span></label>
          <input class="input" type="password" id="password" name="password" required autocomplete="new-password">
          <p class="hint">Mínimo 10 caracteres, con letras y números.</p>
          <?php if (isset($errors['password'])): ?><p class="error-text"><?= e($errors['password']) ?></p><?php endif; ?>
        </div>
        <div class="field<?= $err('password_confirm') ?>">
          <label class="label" for="password_confirm">Repetir <span class="req">*</span></label>
          <input class="input" type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password">
          <?php if (isset($errors['password_confirm'])): ?><p class="error-text"><?= e($errors['password_confirm']) ?></p><?php endif; ?>
        </div>
      </div>

      <div class="field<?= $err('consent') ?>">
        <div class="check">
          <input type="checkbox" id="consent" name="consent" value="1" required <?= old('consent') ? 'checked' : '' ?>>
          <span>
            <label class="t" for="consent" style="cursor:pointer;display:block">Acepto el tratamiento de mis datos</label>
            <span class="d">Según la <a href="<?= url('/privacy') ?>" style="color:var(--sky)">política de privacidad</a>.</span>
          </span>
        </div>
        <?php if (isset($errors['consent'])): ?><p class="error-text"><?= e($errors['consent']) ?></p><?php endif; ?>
      </div>

      <button type="submit" class="btn btn-primary btn-block">Crear cuenta <?= btnIcon('arrow') ?></button>
    </form>

    <p class="text-sm muted center mt-3">
      ¿Ya tienes cuenta? <a href="<?= url('/login') ?>" style="color:var(--mint);font-weight:600">Entrar</a>
    </p>
  </div>
</div>
