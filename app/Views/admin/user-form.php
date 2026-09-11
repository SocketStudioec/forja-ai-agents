<?php
/** @var array|null $user @var array $errors */
use App\Core\Csrf;

$isEdit = $user !== null;
$action = $isEdit ? url('/admin/users/' . $user['id'] . '/edit') : url('/admin/users/new');
$err    = static fn (string $k): string => isset($errors[$k]) ? ' has-error' : '';
?>
<div class="page-head">
  <div>
    <span class="eyebrow"><span class="dot"></span>Usuarios</span>
    <h1 style="margin-top:.9rem"><?= $isEdit ? e($user['email']) : 'Nuevo usuario' ?></h1>
    <p><?= $isEdit ? 'Modifica los datos, el rol o el estado de la cuenta.' : 'La contraseña inicial se le comunica por correo.' ?></p>
  </div>
  <div class="btn-row">
    <a class="btn btn-ghost btn-sm" href="<?= url('/admin/users') ?>">Volver</a>
  </div>
</div>

<form method="post" action="<?= e($action) ?>" novalidate>
  <?= Csrf::field() ?>

  <div class="shellbox" style="max-width:640px">
    <div class="core pad-lg">
      <div class="form-grid">
        <div class="field<?= $err('name') ?>">
          <label class="label" for="name">Nombre <span class="req">*</span></label>
          <input class="input" type="text" id="name" name="name" required maxlength="80"
                 value="<?= e(old('name', $isEdit ? $user['name'] : '')) ?>">
          <?php if (isset($errors['name'])): ?><p class="error-text"><?= e($errors['name']) ?></p><?php endif; ?>
        </div>
        <div class="field">
          <label class="label" for="lastname">Apellido</label>
          <input class="input" type="text" id="lastname" name="lastname" maxlength="80"
                 value="<?= e(old('lastname', $isEdit ? (string) $user['lastname'] : '')) ?>">
        </div>
      </div>

      <div class="field<?= $err('username') ?>">
        <label class="label" for="username">Usuario <span class="req">*</span></label>
        <input class="input mono" type="text" id="username" name="username" required maxlength="40"
               value="<?= e(old('username', $isEdit ? $user['username'] : '')) ?>">
        <?php if (isset($errors['username'])): ?><p class="error-text"><?= e($errors['username']) ?></p><?php endif; ?>
      </div>

      <div class="field<?= $err('email') ?>">
        <label class="label" for="email">Correo <span class="req">*</span></label>
        <input class="input" type="email" id="email" name="email" required maxlength="190"
               value="<?= e(old('email', $isEdit ? $user['email'] : '')) ?>">
        <?php if (isset($errors['email'])): ?><p class="error-text"><?= e($errors['email']) ?></p><?php endif; ?>
      </div>

      <div class="form-grid">
        <div class="field<?= $err('role') ?>">
          <label class="label" for="role">Rol</label>
          <select class="select" id="role" name="role">
            <?php $curRole = old('role', $isEdit ? $user['role'] : 'user'); ?>
            <option value="user"  <?= $curRole === 'user' ? 'selected' : '' ?>>Usuario</option>
            <option value="admin" <?= $curRole === 'admin' ? 'selected' : '' ?>>Administrador</option>
          </select>
          <?php if (isset($errors['role'])): ?><p class="error-text"><?= e($errors['role']) ?></p><?php endif; ?>
        </div>
        <div class="field<?= $err('status') ?>">
          <label class="label" for="status">Estado</label>
          <select class="select" id="status" name="status">
            <?php $curStatus = old('status', $isEdit ? $user['status'] : 'active'); ?>
            <option value="active"    <?= $curStatus === 'active' ? 'selected' : '' ?>>Activo</option>
            <option value="suspended" <?= $curStatus === 'suspended' ? 'selected' : '' ?>>Suspendido</option>
          </select>
        </div>
      </div>

      <div class="field<?= $err('password') ?>">
        <label class="label" for="password">
          Contraseña <?= $isEdit ? '' : '<span class="req">*</span>' ?>
        </label>
        <input class="input" type="password" id="password" name="password" autocomplete="new-password"
               <?= $isEdit ? '' : 'required' ?>>
        <p class="hint">
          <?= $isEdit ? 'Déjalo vacío para conservar la actual.' : 'Mínimo 10 caracteres, con letras y números.' ?>
        </p>
        <?php if (isset($errors['password'])): ?><p class="error-text"><?= e($errors['password']) ?></p><?php endif; ?>
      </div>

      <button type="submit" class="btn btn-primary">
        <?= $isEdit ? 'Guardar cambios' : 'Crear usuario' ?> <?= btnIcon('check') ?>
      </button>
    </div>
  </div>
</form>
