<?php
/** @var array $stats @var array $topSkills @var array $activity @var array $agents @var array $authUser */
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Str;
use App\Core\View;
use App\Models\Skill;
?>
<div class="page-head">
  <div>
    <span class="eyebrow"><span class="dot"></span>Panel</span>
    <h1 style="margin-top:.9rem">Hola, <?= e($authUser['name']) ?></h1>
    <p><?= $sinContenido
        ? 'Todo el catálogo está disponible para que lo descargues.'
        : 'Todo lo que has publicado y cómo se está usando.' ?></p>
  </div>
  <div class="btn-row">
    <?php if ($sinContenido): ?>
      <a class="btn btn-ghost btn-sm" href="<?= url('/dashboard/agents/new') ?>">Crear un agente <?= btnIcon('plus') ?></a>
      <a class="btn btn-primary btn-sm" href="<?= url('/agents') ?>">Ver el catálogo <?= btnIcon('arrow') ?></a>
    <?php else: ?>
      <a class="btn btn-ghost btn-sm" href="<?= url('/dashboard/skills/new') ?>">Nueva skill <?= btnIcon('plus') ?></a>
      <a class="btn btn-primary btn-sm" href="<?= url('/dashboard/agents/new') ?>">Nuevo agente <?= btnIcon('plus') ?></a>
    <?php endif; ?>
  </div>
</div>

<?php if ($sinContenido): ?>
  <!-- Quien acaba de registrarse no viene a ver sus cajas vacías: viene a ver
       qué puede descargar. El catálogo va primero. -->
  <div class="shellbox reveal mb-2">
    <div class="core pad-lg">
      <div class="row" style="align-items:flex-start;gap:1.4rem">
        <div style="min-width:0;flex:1">
          <span class="eyebrow"><span class="dot"></span>Todo listo para descargar</span>
          <h2 style="margin-top:1.1rem;font-size:clamp(1.35rem,2.8vw,1.9rem)">
            Tienes <?= (int) $catalogo['agents'] ?> agentes y <?= (int) $catalogo['skills'] ?> habilidades a tu disposición
          </h2>
          <p class="muted mt-2" style="max-width:56ch">
            Todo el catálogo es público. Entra a cualquier agente, quédate sólo con las
            habilidades que vayas a usar y descarga el paquete. No hace falta pedir permiso
            ni esperar aprobación de nadie.
          </p>
          <div class="btn-row mt-3">
            <a class="btn btn-primary" href="<?= url('/agents') ?>">Explorar agentes <?= btnIcon('arrow') ?></a>
            <a class="btn btn-ghost" href="<?= url('/skills') ?>">Ver habilidades</a>
            <a class="btn btn-ghost" href="<?= url('/builder') ?>">Armar mi paquete <?= btnIcon('layers') ?></a>
          </div>
        </div>

        <div class="stack-sm hide-sm" style="flex:none;width:230px">
          <?php
          $pasos = [
              ['agent',    'Elige un agente'],
              ['layers',   'Quita lo que no uses'],
              ['download', 'Descarga el ZIP'],
          ];
          foreach ($pasos as $i => [$ico, $texto]): ?>
            <div class="format-item">
              <span class="ext"><?= $i + 1 ?></span>
              <span class="t"><?= e($texto) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <?php if ($destacados): ?>
    <div class="row" style="align-items:flex-end;margin:2rem 0 1.1rem">
      <h2 style="font-size:1.15rem">Empieza por aquí</h2>
      <a class="btn btn-ghost btn-sm push" href="<?= url('/agents') ?>">Ver los <?= (int) $catalogo['agents'] ?> <?= btnIcon('arrow') ?></a>
    </div>
    <div class="grid mb-2">
      <?php foreach ($destacados as $i => $d): ?>
        <?= View::partial('partials/agent-card', ['agent' => $d, 'delay' => $i + 1]) ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="row" style="align-items:flex-end;margin:2.2rem 0 1.1rem">
    <h2 style="font-size:1.15rem">Tu espacio</h2>
    <span class="badge push">todavía vacío</span>
  </div>
<?php endif; ?>

<div class="stats">
  <?php
  $cards = [
      ['Agentes',     $stats['agents'],    'Creados por ti',        ''],
      ['Skills',      $stats['skills'],    'En tu biblioteca',      ''],
      ['Publicados',  $stats['published'], 'Visibles al público',   'accent'],
      ['Pendientes',  $stats['pending'],   'Esperando revisión',    'warn'],
      ['Descargas',   $stats['downloads'], 'Acumuladas',            ''],
      ['Favoritos',   $stats['favorites'], 'Guardados por ti',      ''],
  ];
  foreach ($cards as $i => [$k, $v, $d, $tone]): ?>
    <div class="shellbox tight stat <?= e($tone) ?> reveal" data-d="<?= min(4, $i + 1) ?>">
      <div class="core">
        <span class="k"><?= e($k) ?></span>
        <span class="v"><?= e(Str::compactNumber((int) $v)) ?></span>
        <span class="d"><?= e($d) ?></span>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="bento mt-3">
  <!-- ------------------------------------------------------------ Agentes -->
  <div class="shellbox w8 reveal">
    <div class="core">
      <div class="pad" style="border-bottom:1px solid var(--line)">
        <div class="row">
          <h2 style="font-size:1.05rem">Mis agentes</h2>
          <a class="btn btn-ghost btn-sm push" href="<?= url('/dashboard/agents') ?>">Gestionar <?= btnIcon('arrow') ?></a>
        </div>
      </div>

      <?php if ($agents): ?>
        <div class="table-wrap">
          <table class="data">
            <thead>
              <tr><th>Agente</th><th>Skills</th><th>Estado</th><th class="num">Descargas</th></tr>
            </thead>
            <tbody>
              <?php foreach (array_slice($agents, 0, 6) as $a): ?>
                <tr>
                  <td>
                    <a class="row-main" href="<?= url('/agents/' . $a['slug']) ?>"><?= e($a['name']) ?></a>
                    <span class="row-sub mono">v<?= e($a['version']) ?> · <?= e($a['slug']) ?></span>
                  </td>
                  <td class="mono"><?= (int) $a['skills_count'] ?></td>
                  <td><span class="badge <?= $a['status'] === 'published' ? 'badge-mint' : '' ?>">
                    <?= e(Skill::statusLabel((string) $a['status'])) ?></span></td>
                  <td class="num"><?= e(Str::compactNumber((int) $a['downloads'])) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty">
          <span class="glyph"><?= icon('agent', 22) ?></span>
          <h3>Todavía no has creado ningún agente</h3>
          <p>Un agente son unas reglas en Markdown más las habilidades que quieras enlazarle.</p>
          <a class="btn btn-primary btn-sm mt-1" href="<?= url('/dashboard/agents/new') ?>">
            Crear el primero <?= btnIcon('plus') ?>
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- --------------------------------------------------------- Actividad -->
  <div class="shellbox reveal" data-d="2">
    <div class="core">
      <div class="pad" style="border-bottom:1px solid var(--line)">
        <h2 style="font-size:1.05rem">Actividad reciente</h2>
      </div>
      <div class="pad">
        <?php if ($activity): ?>
          <div class="meta-list">
            <?php foreach ($activity as $a): ?>
              <div>
                <span class="k"><?= e(Audit::label((string) $a['action'])) ?></span>
                <span class="v" style="font-size:.72rem"><?= e(Str::timeAgo((string) $a['created_at'])) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-sm muted">Sin movimientos todavía.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ------------------------------------------------- Skills más bajadas -->
  <div class="shellbox w12 reveal" data-d="3">
    <div class="core">
      <div class="pad" style="border-bottom:1px solid var(--line)">
        <div class="row">
          <h2 style="font-size:1.05rem">Tus skills más descargadas</h2>
          <a class="btn btn-ghost btn-sm push" href="<?= url('/dashboard/skills') ?>">Ver todas <?= btnIcon('arrow') ?></a>
        </div>
      </div>

      <?php if ($topSkills): ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>Skill</th><th>Estado</th><th class="num">Descargas</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($topSkills as $s): ?>
                <tr>
                  <td><span class="row-main"><?= e($s['name']) ?></span>
                      <span class="row-sub mono"><?= e($s['slug']) ?></span></td>
                  <td><span class="badge <?= $s['status'] === 'published' ? 'badge-mint' : '' ?>">
                    <?= e(Skill::statusLabel((string) $s['status'])) ?></span></td>
                  <td class="num"><?= e(Str::compactNumber((int) $s['downloads'])) ?></td>
                  <td class="row-actions">
                    <a class="btn btn-ghost btn-sm" href="<?= url('/skills/' . $s['slug']) ?>">Ver</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty">
          <span class="glyph"><?= icon('skill', 22) ?></span>
          <h3>Sin habilidades todavía</h3>
          <p>Crea una habilidad y enlázala a tus agentes.</p>
          <a class="btn btn-primary btn-sm mt-1" href="<?= url('/dashboard/skills/new') ?>">Nueva skill <?= btnIcon('plus') ?></a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
