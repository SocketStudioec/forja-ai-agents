<?php
/** @var array $result @var array $filters */
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Str;
use App\Core\View;
use App\Models\User;
?>
<div class="page-head">
  <div>
    <span class="eyebrow"><span class="dot"></span>Administración</span>
    <h1 style="margin-top:.9rem">Usuarios</h1>
    <p><?= (int) $result['total'] ?> cuenta<?= $result['total'] === 1 ? '' : 's' ?> registradas.</p>
  </div>
  <div class="btn-row">
    <a class="btn btn-primary btn-sm" href="<?= url('/admin/users/new') ?>">Nuevo usuario <?= btnIcon('plus') ?></a>
  </div>
</div>

<form class="toolbar mb-2" method="get" action="<?= url('/admin/users') ?>">
  <label class="search">
    <span class="sr-only">Buscar usuario</span>
    <?= icon('search') ?>
    <input class="input" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Nombre, usuario o correo…">
  </label>
  <label class="filter-select">
    <span class="sr-only">Rol</span>
    <select class="select" name="role" data-autosubmit>
      <option value="">Todos los roles</option>
      <option value="admin" <?= $filters['role'] === 'admin' ? 'selected' : '' ?>>Administradores</option>
      <option value="user"  <?= $filters['role'] === 'user' ? 'selected' : '' ?>>Usuarios</option>
    </select>
  </label>
  <label class="filter-select">
    <span class="sr-only">Estado</span>
    <select class="select" name="status" data-autosubmit>
      <option value="">Todos los estados</option>
      <option value="active"    <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Activos</option>
      <option value="suspended" <?= $filters['status'] === 'suspended' ? 'selected' : '' ?>>Suspendidos</option>
    </select>
  </label>
  <button class="btn btn-primary btn-sm" type="submit">Filtrar <?= btnIcon('filter') ?></button>
</form>

<div class="shellbox">
  <div class="core">
    <?php if ($result['items']): ?>
      <div class="table-wrap">
        <table class="data">
          <thead>
            <tr><th>Usuario</th><th>Correo</th><th>Rol</th><th class="num">Contenido</th><th>Estado</th><th>Registro</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($result['items'] as $u):
                $isMe = (int) $u['id'] === Auth::id(); ?>
              <tr>
                <td>
                  <span class="row-main"><?= e(Auth::displayName($u)) ?><?= $isMe ? ' <span class="badge">tú</span>' : '' ?></span>
                  <span class="row-sub mono"><?= e($u['username']) ?></span>
                </td>
                <td class="text-xs"><?= e($u['email']) ?></td>
                <td><span class="badge <?= $u['role'] === 'admin' ? 'badge-mint' : '' ?>">
                  <?= e(User::roleLabel((string) $u['role'])) ?></span></td>
                <td class="num mono"><?= (int) $u['agents_count'] ?>a / <?= (int) $u['skills_count'] ?>s</td>
                <td><span class="badge <?= $u['status'] === 'active' ? 'badge-mint' : 'badge-danger' ?>">
                  <?= e(User::statusLabel((string) $u['status'])) ?></span></td>
                <td class="text-xs muted nowrap"><?= e(Str::timeAgo((string) $u['created_at'])) ?></td>
                <td>
                  <div class="row-actions">
                    <a class="btn btn-ghost btn-sm" href="<?= url('/admin/users/' . $u['id'] . '/edit') ?>">Editar</a>
                    <?php if (!$isMe): ?>
                      <form method="post" action="<?= url('/admin/users/' . $u['id'] . '/status') ?>">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="status" value="<?= $u['status'] === 'active' ? 'suspended' : 'active' ?>">
                        <button class="btn btn-ghost btn-sm" type="submit">
                          <?= $u['status'] === 'active' ? 'Suspender' : 'Activar' ?>
                        </button>
                      </form>
                      <form method="post" action="<?= url('/admin/users/' . $u['id'] . '/delete') ?>"
                            data-confirm="Se elimina la cuenta de <?= e($u['email']) ?> y sus datos personales. ¿Continuar?">
                        <?= Csrf::field() ?>
                        <button class="icon-btn" type="submit" aria-label="Eliminar"><?= icon('trash', 15) ?></button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty">
        <span class="glyph"><?= icon('users', 22) ?></span>
        <h3>Ningún usuario coincide</h3>
        <p>Ajusta la búsqueda o los filtros.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<?= View::partial('partials/pager', ['page' => $result['page'], 'pages' => $result['pages'], 'path' => '/admin/users']) ?>
