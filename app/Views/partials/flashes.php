<?php
/** @var array $flashes */
if (empty($flashes)) {
    return;
}
$iconFor = ['ok' => 'check', 'error' => 'alert', 'info' => 'info'];
?>
<div class="flash-stack" id="flashStack" role="status" aria-live="polite">
  <?php foreach ($flashes as $f):
      $type = in_array($f['type'], ['ok', 'error', 'info'], true) ? $f['type'] : 'info'; ?>
    <div class="flash <?= e($type) ?>">
      <span class="ico"><?= icon($iconFor[$type], 17) ?></span>
      <span><?= e($f['message']) ?></span>
      <button type="button" class="x" aria-label="Cerrar aviso">&times;</button>
    </div>
  <?php endforeach; ?>
</div>
