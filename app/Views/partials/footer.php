<?php use App\Core\Config; ?>
<footer class="footer">
  <div class="shell">
    <div class="footer-grid">
      <div>
        <a class="brand" href="<?= url('/') ?>">
          <span class="brand-mark" aria-hidden="true"><?= e(mb_strtoupper(mb_substr(Config::appName(), 0, 1))) ?></span>
          <span class="brand-name"><?= e(Config::appName()) ?></span>
        </a>
        <p class="text-sm muted mt-2" style="max-width:34ch">
          Agentes listos para trabajar. Las reglas viajan en Markdown, las habilidades en JSON,
          y te llevas sólo lo que elijas.
        </p>
      </div>

      <div>
        <h2>Catálogo</h2>
        <ul>
          <li><a href="<?= url('/agents') ?>">Agentes</a></li>
          <li><a href="<?= url('/skills') ?>">Habilidades</a></li>
          <li><a href="<?= url('/categories') ?>">Categorías</a></li>
          <li><a href="<?= url('/builder') ?>">Arma tu paquete</a></li>
        </ul>
      </div>

      <div>
        <h2>Participar</h2>
        <ul>
          <li><a href="<?= url('/submit') ?>">Enviar una skill</a></li>
          <li><a href="<?= url('/register') ?>">Crear cuenta</a></li>
          <li><a href="<?= url('/login') ?>">Iniciar sesión</a></li>
        </ul>
      </div>

      <div>
        <h2>Recursos</h2>
        <ul>
          <li><a href="<?= url('/docs/openclaw') ?>">Documentación</a></li>
          <li><a href="<?= url('/privacy') ?>">Privacidad</a></li>
        </ul>
      </div>
    </div>

    <div class="footer-note">
      <span>© <?= date('Y') ?> <?= e(Config::appName()) ?></span>
      <span class="right">
        <a href="<?= url('/privacy') ?>">Tratamiento de datos</a>
        <a href="<?= url('/docs/openclaw') ?>">Formato SKILL.md</a>
      </span>
    </div>
  </div>
</footer>
