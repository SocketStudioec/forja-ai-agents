<?php
/** @var array $errors @var array $authUser */
use App\Core\Auth;
use App\Core\Csrf;
use App\Models\User;

$err = static fn (string $k): string => isset($errors[$k]) ? ' has-error' : '';
?>
<div class="page-head">
  <div>
    <span class="eyebrow"><span class="dot"></span>Cuenta</span>
    <h1 style="margin-top:.9rem">Mi cuenta</h1>
    <p>Tus datos, tu contraseña y tus derechos sobre la información que guardamos.</p>
  </div>
</div>

<div class="detail-grid">
  <div class="stack">
    <form method="post" action="<?= url('/dashboard/account') ?>" novalidate>
      <?= Csrf::field() ?>

      <div class="shellbox">
        <div class="core pad-lg">
          <h2 style="font-size:1.05rem">Datos personales</h2>

          <div class="form-grid mt-2">
            <div class="field<?= $err('name') ?>">
              <label class="label" for="name">Nombre <span class="req">*</span></label>
              <input class="input" type="text" id="name" name="name" required maxlength="80"
                     value="<?= e(old('name', $authUser['name'])) ?>">
              <?php if (isset($errors['name'])): ?><p class="error-text"><?= e($errors['name']) ?></p><?php endif; ?>
            </div>
            <div class="field">
              <label class="label" for="lastname">Apellido</label>
              <input class="input" type="text" id="lastname" name="lastname" maxlength="80"
                     value="<?= e(old('lastname', (string) $authUser['lastname'])) ?>">
            </div>
          </div>

          <div class="field<?= $err('email') ?>">
            <label class="label" for="email">Correo <span class="req">*</span></label>
            <input class="input" type="email" id="email" name="email" required maxlength="190"
                   value="<?= e(old('email', $authUser['email'])) ?>">
            <?php if (isset($errors['email'])): ?><p class="error-text"><?= e($errors['email']) ?></p><?php endif; ?>
          </div>

          <div class="field<?= $err('bio') ?>">
            <label class="label" for="bio">
              Sobre ti
              <span class="push faint text-xs mono" id="bioCount"></span>
            </label>
            <textarea class="textarea" id="bio" name="bio" maxlength="280" style="min-height:90px"
                      data-counter="#bioCount"
                      placeholder="Aparece junto a lo que publicas."><?= e(old('bio', (string) $authUser['bio'])) ?></textarea>
          </div>

          <hr class="divider" style="margin:1.6rem 0">

          <h2 style="font-size:1.05rem">Cambiar contraseña</h2>
          <p class="text-sm muted mt-1">Déjalo vacío si no quieres cambiarla.</p>

          <div class="field<?= $err('current_password') ?>" style="margin-top:1.1rem">
            <label class="label" for="current_password">Contraseña actual</label>
            <input class="input" type="password" id="current_password" name="current_password" autocomplete="current-password">
            <?php if (isset($errors['current_password'])): ?><p class="error-text"><?= e($errors['current_password']) ?></p><?php endif; ?>
          </div>

          <div class="form-grid">
            <div class="field<?= $err('password') ?>">
              <label class="label" for="password">Contraseña nueva</label>
              <input class="input" type="password" id="password" name="password" autocomplete="new-password">
              <?php if (isset($errors['password'])): ?><p class="error-text"><?= e($errors['password']) ?></p><?php endif; ?>
            </div>
            <div class="field<?= $err('password_confirm') ?>">
              <label class="label" for="password_confirm">Repetir</label>
              <input class="input" type="password" id="password_confirm" name="password_confirm" autocomplete="new-password">
              <?php if (isset($errors['password_confirm'])): ?><p class="error-text"><?= e($errors['password_confirm']) ?></p><?php endif; ?>
            </div>
          </div>

          <button type="submit" class="btn btn-primary mt-2">Guardar cambios <?= btnIcon('check') ?></button>
        </div>
      </div>
    </form>

    <!-- ---------------------------------------------------- Baja de cuenta -->
    <div class="shellbox">
      <div class="core pad-lg">
        <h2 style="font-size:1.05rem">Eliminar mi cuenta</h2>
        <p class="text-sm muted mt-1">
          Se borran tus datos personales de forma permanente: nombre, correo, usuario y contraseña.
          El contenido que hayas publicado permanece en la biblioteca pero queda sin autor asociado,
          para no romper los enlaces de quienes ya lo descargaron. Si prefieres que también se elimine,
          borra antes tus agentes y habilidades desde el panel.
        </p>

        <form method="post" action="<?= url('/dashboard/account/delete') ?>" class="mt-3"
              data-confirm="Esta acción no se puede deshacer. ¿Eliminar tu cuenta y tus datos personales?">
          <?= Csrf::field() ?>
          <div class="field<?= $err('password') ?>" style="max-width:340px">
            <label class="label" for="delete_password">Confirma con tu contraseña</label>
            <input class="input" type="password" id="delete_password" name="password" required autocomplete="current-password">
          </div>
          <button type="submit" class="btn btn-danger">Eliminar mi cuenta <?= btnIcon('trash') ?></button>
        </form>
      </div>
    </div>
  </div>

  <aside class="side-stack">
    <div class="shellbox tight">
      <div class="core pad">
        <h3 style="font-size:.95rem">Tu perfil</h3>
        <div class="row mt-2">
          <span class="card-glyph" aria-hidden="true"><?= e(Auth::initials($authUser)) ?></span>
          <div style="min-width:0">
            <strong style="display:block;font-size:.9rem"><?= e(Auth::displayName($authUser)) ?></strong>
            <span class="text-xs mono faint"><?= e($authUser['username']) ?></span>
          </div>
        </div>
        <div class="meta-list mt-2">
          <div><span class="k">Rol</span><span class="v"><?= e(User::roleLabel((string) $authUser['role'])) ?></span></div>
          <div><span class="k">Estado</span><span class="v"><?= e(User::statusLabel((string) $authUser['status'])) ?></span></div>
          <div><span class="k">Alta</span><span class="v"><?= e(date('d/m/Y', strtotime((string) $authUser['created_at']))) ?></span></div>
        </div>
      </div>
    </div>

    <div class="shellbox tight">
      <div class="core pad">
        <h3 style="font-size:.95rem">Tus datos</h3>
        <p class="text-sm muted mt-1">
          Puedes consultar en cualquier momento qué guardamos y por qué.
        </p>
        <a class="btn btn-ghost btn-sm btn-block mt-2" href="<?= url('/privacy') ?>">
          Política de privacidad <?= btnIcon('arrow') ?>
        </a>
      </div>
    </div>
  </aside>
</div>
