<?php /** @var string $reference @var array|null $submission */ ?>
<section class="section">
  <div class="shell shell-narrow">
    <div class="shellbox reveal">
      <div class="core pad-lg center">
        <span class="card-glyph" style="width:56px;height:56px;border-radius:17px;margin:0 auto 1.4rem" aria-hidden="true">
          <?= icon('check', 24) ?>
        </span>

        <h1 style="font-size:clamp(1.7rem,4vw,2.4rem)">Skill enviada correctamente</h1>
        <p class="lede mt-2" style="margin-inline:auto">
          Hemos recibido tu envío. Será revisado por nuestro equipo antes de publicarse en la biblioteca.
        </p>

        <?php if ($reference !== ''): ?>
          <div class="shellbox tight mt-3" style="text-align:left">
            <div class="core pad">
              <div class="meta-list">
                <div><span class="k">Identificador</span><span class="v"><?= e($reference) ?></span></div>
                <?php if ($submission): ?>
                  <div><span class="k">Nombre</span><span class="v"><?= e($submission['skill_name']) ?></span></div>
                  <div><span class="k">Estado</span><span class="v">Pendiente de revisión</span></div>
                  <div><span class="k">Recibido</span><span class="v"><?= e(date('d/m/Y H:i', strtotime((string) $submission['created_at']))) ?></span></div>
                <?php endif; ?>
              </div>
              <button type="button" class="btn btn-ghost btn-sm mt-2" data-copy="<?= e($reference) ?>"
                      data-copy-msg="Identificador copiado">
                Copiar identificador <?= btnIcon('copy') ?>
              </button>
            </div>
          </div>
        <?php endif; ?>

        <div class="notice mt-3" style="text-align:left">
          <?= icon('mail', 17) ?>
          <span>
            Te enviamos una confirmación por correo con este identificador. Guárdalo: lo necesitas
            si quieres consultar el estado o pedir que eliminemos tus datos.
          </span>
        </div>

        <div class="btn-row mt-3" style="justify-content:center">
          <a class="btn btn-primary" href="<?= url('/skills') ?>">Explorar la biblioteca <?= btnIcon('arrow') ?></a>
          <a class="btn btn-ghost" href="<?= url('/submit') ?>">Enviar otra</a>
        </div>
      </div>
    </div>
  </div>
</section>
