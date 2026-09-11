<?php
/** @var array $categories @var array $errors */
use App\Core\Csrf;
?>
<div class="page-head">
  <div>
    <span class="eyebrow"><span class="dot"></span>Administración</span>
    <h1 style="margin-top:.9rem">Categorías</h1>
    <p>Ordenan el catálogo. Al eliminar una, su contenido queda sin categoría.</p>
  </div>
</div>

<div class="detail-grid">
  <div class="shellbox">
    <div class="core">
      <?php if ($categories): ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Nombre</th><th>Slug</th><th>Orden</th><th>Estado</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($categories as $c): ?>
                <tr>
                  <td>
                    <span class="row-main"><?= e($c['name']) ?></span>
                    <?php if (!empty($c['description'])): ?>
                      <span class="row-sub"><?= e($c['description']) ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="mono text-xs"><?= e($c['slug']) ?></td>
                  <td class="mono"><?= (int) $c['position'] ?></td>
                  <td><span class="badge <?= $c['status'] === 'active' ? 'badge-mint' : '' ?>">
                    <?= $c['status'] === 'active' ? 'Activa' : 'Oculta' ?></span></td>
                  <td>
                    <div class="row-actions">
                      <form method="post" action="<?= url('/admin/categories') ?>">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                        <input type="hidden" name="name" value="<?= e($c['name']) ?>">
                        <input type="hidden" name="description" value="<?= e((string) $c['description']) ?>">
                        <input type="hidden" name="position" value="<?= (int) $c['position'] ?>">
                        <input type="hidden" name="status" value="<?= $c['status'] === 'active' ? 'hidden' : 'active' ?>">
                        <button class="btn btn-ghost btn-sm" type="submit">
                          <?= $c['status'] === 'active' ? 'Ocultar' : 'Activar' ?>
                        </button>
                      </form>
                      <form method="post" action="<?= url('/admin/categories/' . $c['id'] . '/delete') ?>"
                            data-confirm="Se elimina «<?= e($c['name']) ?>». El contenido asociado queda sin categoría. ¿Continuar?">
                        <?= Csrf::field() ?>
                        <button class="icon-btn" type="submit" aria-label="Eliminar"><?= icon('trash', 15) ?></button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty">
          <span class="glyph"><?= icon('tag', 22) ?></span>
          <h3>Sin categorías</h3>
          <p>Crea la primera con el formulario de al lado.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <aside class="side-stack">
    <div class="shellbox tight">
      <div class="core pad">
        <h3 style="font-size:.95rem">Nueva categoría</h3>

        <form method="post" action="<?= url('/admin/categories') ?>" class="mt-2" novalidate>
          <?= Csrf::field() ?>

          <div class="field<?= isset($errors['name']) ? ' has-error' : '' ?>">
            <label class="label" for="name">Nombre <span class="req">*</span></label>
            <input class="input" type="text" id="name" name="name" required maxlength="90"
                   value="<?= e(old('name')) ?>" placeholder="Contabilidad">
            <?php if (isset($errors['name'])): ?><p class="error-text"><?= e($errors['name']) ?></p><?php endif; ?>
          </div>

          <div class="field">
            <label class="label" for="description">Descripción</label>
            <input class="input" type="text" id="description" name="description" maxlength="255"
                   value="<?= e(old('description')) ?>" placeholder="Cierres, impuestos y conciliaciones.">
          </div>

          <div class="form-grid">
            <div class="field">
              <label class="label" for="position">Orden</label>
              <input class="input mono" type="number" id="position" name="position" value="<?= e(old('position', '0')) ?>" min="0" max="999">
            </div>
            <div class="field">
              <label class="label" for="status">Estado</label>
              <select class="select" id="status" name="status">
                <option value="active">Activa</option>
                <option value="hidden">Oculta</option>
              </select>
            </div>
          </div>

          <button class="btn btn-primary btn-block" type="submit">Crear categoría <?= btnIcon('plus') ?></button>
        </form>
      </div>
    </div>
  </aside>
</div>
