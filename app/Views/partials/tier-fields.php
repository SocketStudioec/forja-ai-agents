<?php
/**
 * Bloque «plantilla de pago» del formulario. Sólo se pinta para
 * administradores; la comprobación de verdad está en Tier::resolve, que ignora
 * estos campos si quien envía no lo es.
 *
 * @var array|null $row  agente o habilidad en edición
 */
use App\Core\Auth;
use App\Core\Tier;

if (!Auth::isAdmin()) {
    return;
}

$tierActual = (string) ($row['tier'] ?? Tier::FREE);
$esPago     = $tierActual === Tier::PAID;
?>
<div class="shellbox tight">
  <div class="core pad">
    <h3 style="font-size:.95rem">Tipo de plantilla</h3>
    <p class="hint" style="margin-top:.4rem">Sólo los administradores ven esta sección.</p>

    <div class="stack-sm mt-2">
      <?php foreach (Tier::all() as $t): ?>
        <label class="check">
          <input type="radio" name="tier" value="<?= e($t) ?>" <?= $tierActual === $t ? 'checked' : '' ?>>
          <span>
            <span class="t"><?= e(Tier::label($t)) ?></span>
            <span class="d"><?= e(Tier::hint($t)) ?></span>
          </span>
        </label>
      <?php endforeach; ?>
    </div>

    <div class="field mt-2">
      <label class="label" for="price_label">Precio que se muestra</label>
      <input class="input" type="text" id="price_label" name="price_label" maxlength="60"
             value="<?= e((string) ($row['price_label'] ?? '')) ?>" placeholder="USD 49 · pago único">
      <p class="hint">Texto libre. Se muestra tal cual en la ficha. Déjalo vacío si prefieres no publicar precio.</p>
    </div>

    <div class="field">
      <label class="label" for="contact_url">Enlace de contacto</label>
      <input class="input" type="text" id="contact_url" name="contact_url" maxlength="255"
             value="<?= e((string) ($row['contact_url'] ?? '')) ?>" placeholder="https://wa.me/593…">
      <p class="hint">A dónde va quien pulsa «Quiero esta plantilla». Si lo dejas vacío se usa el correo de administración.</p>
    </div>

    <div class="field">
      <label class="label" for="teaser">Qué hace, sin revelar cómo</label>
      <textarea class="textarea" id="teaser" name="teaser" style="min-height:120px"
                placeholder="Describe el resultado que entrega y para quién es. Este texto sí se ve; las reglas no."><?= e((string) ($row['teaser'] ?? '')) ?></textarea>
      <p class="hint">Sólo se usa en las plantillas de pago, donde el contenido no se previsualiza.</p>
    </div>

    <?php if ($esPago): ?>
      <div class="notice warn mt-1">
        <?= icon('alert', 17) ?>
        <span>
          Marcada como de pago: no se descarga, no se previsualiza su contenido y
          no entra en los paquetes del constructor.
        </span>
      </div>
    <?php endif; ?>
  </div>
</div>
