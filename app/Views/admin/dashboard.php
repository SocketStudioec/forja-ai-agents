<?php
/** @var array $stats @var array $topSkills @var array $topAgents @var array $newUsers @var array $activity @var array $pendingList */
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Str;
use App\Models\Skill;
use App\Models\Submission;
use App\Models\User;
?>
<div class="page-head">
  <div>
    <span class="eyebrow"><span class="dot"></span>Administración</span>
    <h1 style="margin-top:.9rem">Resumen</h1>
    <p>Estado de la plataforma en un vistazo.</p>
  </div>
  <div class="btn-row">
    <?php if ($stats['pending'] > 0): ?>
      <a class="btn btn-primary btn-sm" href="<?= url('/admin/submissions') ?>">
        <?= (int) $stats['pending'] ?> pendiente<?= $stats['pending'] === 1 ? '' : 's' ?> <?= btnIcon('inbox') ?>
      </a>
    <?php endif; ?>
    <a class="btn btn-ghost btn-sm" href="<?= url('/admin/users/new') ?>">Nuevo usuario <?= btnIcon('plus') ?></a>
  </div>
</div>

<div class="stats">
  <?php
  $cards = [
      ['Usuarios',   $stats['users'],      $stats['users_new'] . ' en 30 días', ''],
      ['Agentes',    $stats['agents'],     'En el catálogo',                    ''],
      ['Skills',     $stats['skills'],     'En la biblioteca',                  ''],
      ['Publicados', $stats['published'],  'Visibles al público',               'accent'],
      ['Pendientes', $stats['pending'],    'Esperando revisión',                'warn'],
      ['Descargas',  $stats['downloads'],  $stats['downloads30'] . ' en 30 días', ''],
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

<!-- --------------------------------------------------------- Bandeja urgente -->
<?php if ($pendingList): ?>
  <div class="shellbox mt-3 reveal">
    <div class="core">
      <div class="pad" style="border-bottom:1px solid var(--line)">
        <div class="row">
          <h2 style="font-size:1.05rem">Esperando tu revisión</h2>
          <a class="btn btn-ghost btn-sm push" href="<?= url('/admin/submissions') ?>">Ver bandeja <?= btnIcon('arrow') ?></a>
        </div>
      </div>
      <div class="table-wrap">
        <table class="data">
          <thead><tr><th>Envío</th><th>Remitente</th><th>Tipo</th><th>Recibido</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($pendingList as $p): ?>
              <tr>
                <td>
                  <span class="row-main"><?= e($p['skill_name']) ?></span>
                  <span class="row-sub mono"><?= e($p['reference']) ?></span>
                </td>
                <td>
                  <span class="text-sm"><?= e($p['name'] ?: 'Sin nombre') ?></span>
                  <span class="row-sub"><?= e($p['email']) ?></span>
                </td>
                <td><span class="badge <?= $p['kind'] === 'agent' ? 'badge-mint' : 'badge-sky' ?>">
                  <?= $p['kind'] === 'agent' ? 'Agente' : 'Skill' ?></span></td>
                <td class="text-xs muted nowrap"><?= e(Str::timeAgo((string) $p['created_at'])) ?></td>
                <td class="row-actions">
                  <a class="btn btn-primary btn-sm" href="<?= url('/admin/submissions/' . $p['id']) ?>">Revisar</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>

<div class="bento mt-3">
  <!-- ------------------------------------------------------ Más descargados -->
  <div class="shellbox w6 reveal">
    <div class="core">
      <div class="pad" style="border-bottom:1px solid var(--line)">
        <h2 style="font-size:1.05rem">Agentes más descargados</h2>
      </div>
      <?php if ($topAgents): ?>
        <div class="table-wrap">
          <table class="data" style="min-width:0">
            <thead><tr><th>Agente</th><th>Skills</th><th class="num">Descargas</th></tr></thead>
            <tbody>
              <?php foreach ($topAgents as $a): ?>
                <tr>
                  <td><a class="row-main" href="<?= url('/agents/' . $a['slug']) ?>"><?= e($a['name']) ?></a>
                      <span class="row-sub"><?= e(Skill::statusLabel((string) $a['status'])) ?></span></td>
                  <td class="mono"><?= (int) $a['skills_count'] ?></td>
                  <td class="num"><?= e(Str::compactNumber((int) $a['downloads'])) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="pad"><p class="text-sm muted">Sin agentes todavía.</p></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="shellbox w6 reveal" data-d="2">
    <div class="core">
      <div class="pad" style="border-bottom:1px solid var(--line)">
        <h2 style="font-size:1.05rem">Skills más descargadas</h2>
      </div>
      <?php if ($topSkills): ?>
        <div class="table-wrap">
          <table class="data" style="min-width:0">
            <thead><tr><th>Skill</th><th>Autor</th><th class="num">Descargas</th></tr></thead>
            <tbody>
              <?php foreach ($topSkills as $s): ?>
                <tr>
                  <td><a class="row-main" href="<?= url('/skills/' . $s['slug']) ?>"><?= e($s['name']) ?></a>
                      <span class="row-sub"><?= e(Skill::statusLabel((string) $s['status'])) ?></span></td>
                  <td class="text-xs muted"><?= e($s['author']) ?></td>
                  <td class="num"><?= e(Str::compactNumber((int) $s['downloads'])) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="pad"><p class="text-sm muted">Sin skills todavía.</p></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ------------------------------------------------------ Nuevos usuarios -->
  <div class="shellbox w6 reveal" data-d="3">
    <div class="core">
      <div class="pad" style="border-bottom:1px solid var(--line)">
        <div class="row">
          <h2 style="font-size:1.05rem">Nuevos usuarios</h2>
          <a class="btn btn-ghost btn-sm push" href="<?= url('/admin/users') ?>">Gestionar <?= btnIcon('arrow') ?></a>
        </div>
      </div>
      <div class="table-wrap">
        <table class="data" style="min-width:0">
          <thead><tr><th>Usuario</th><th>Rol</th><th>Alta</th></tr></thead>
          <tbody>
            <?php foreach ($newUsers as $u): ?>
              <tr>
                <td><span class="row-main"><?= e(Auth::displayName($u)) ?></span>
                    <span class="row-sub"><?= e($u['email']) ?></span></td>
                <td><span class="badge <?= $u['role'] === 'admin' ? 'badge-mint' : '' ?>">
                  <?= e(User::roleLabel((string) $u['role'])) ?></span></td>
                <td class="text-xs muted nowrap"><?= e(Str::timeAgo((string) $u['created_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ---------------------------------------------------------- Auditoría -->
  <div class="shellbox w6 reveal" data-d="4">
    <div class="core">
      <div class="pad" style="border-bottom:1px solid var(--line)">
        <div class="row">
          <h2 style="font-size:1.05rem">Actividad reciente</h2>
          <a class="btn btn-ghost btn-sm push" href="<?= url('/admin/audit') ?>">Ver todo <?= btnIcon('arrow') ?></a>
        </div>
      </div>
      <div class="pad">
        <div class="meta-list">
          <?php foreach ($activity as $a): ?>
            <div>
              <span class="k">
                <?= e(Audit::label((string) $a['action'])) ?>
                <span class="faint">· <?= e($a['username'] ?: ($a['actor_label'] ?: 'visitante')) ?></span>
              </span>
              <span class="v" style="font-size:.72rem"><?= e(Str::timeAgo((string) $a['created_at'])) ?></span>
            </div>
          <?php endforeach; ?>
          <?php if (!$activity): ?>
            <p class="text-sm muted">Sin actividad registrada.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
