<?php
/** @var array $agents @var array $skills @var array $categories @var array $stats */
use App\Core\Config;
use App\Core\Str;
use App\Core\View;
?>

<!-- ------------------------------------------------------------------ Hero -->
<section class="hero">
  <div class="shell">
    <div class="hero-grid">
      <div>
        <span class="eyebrow reveal"><span class="dot"></span>Tienda de agentes</span>

        <h1 class="reveal" data-d="1">
          Agentes listos.<br>
          Reglas en <em>.md</em>,<br>
          habilidades en <em>.json</em>.
        </h1>

        <p class="lede reveal" data-d="2">
          Elige el agente que necesitas, quédate sólo con las habilidades que vas a usar
          y descarga un paquete que funciona tal cual, sin configurar nada.
        </p>

        <div class="btn-row hero-actions reveal" data-d="3">
          <a class="btn btn-primary" href="<?= url('/agents') ?>">
            Explorar agentes <?= btnIcon('arrow') ?>
          </a>
          <a class="btn btn-ghost" href="<?= url('/builder') ?>">
            Armar mi paquete <?= btnIcon('layers') ?>
          </a>
        </div>

        <div class="hero-meta reveal" data-d="4">
          <div>
            <span class="n"><?= e(Str::compactNumber($stats['agents'])) ?></span>
            <span class="l">Agentes</span>
          </div>
          <div>
            <span class="n"><?= e(Str::compactNumber($stats['skills'])) ?></span>
            <span class="l">Habilidades</span>
          </div>
          <div>
            <span class="n"><?= e(Str::compactNumber($stats['downloads'])) ?></span>
            <span class="l">Descargas</span>
          </div>
        </div>
      </div>

      <!-- Muestra real de lo que se lleva el usuario -->
      <div class="shellbox code-card reveal" data-d="2">
        <div class="core">
          <div class="code-head">
            <span class="lights" aria-hidden="true"><i></i><i></i><i></i></span>
            <span class="name">analista-tributario/</span>
            <span class="tag badge badge-mint">.zip</span>
          </div>
<pre class="code-body" aria-label="Contenido del paquete descargado"><span class="c">├─</span> <span class="p">AGENT.md</span>          <span class="c"># las reglas</span>
<span class="c">├─</span> agent.json        <span class="c"># manifiesto</span>
<span class="c">└─</span> skills/
   <span class="c">├─</span> <span class="s">conciliar-iva.json</span>
   <span class="c">├─</span> <span class="s">leer-factura.json</span>
   <span class="c">└─</span> <span class="s">armar-ats.json</span>

<span class="c">— AGENT.md —</span>
<span class="k">---</span>
<span class="k">name:</span> analista-tributario
<span class="k">version:</span> <span class="s">1.4.0</span>
<span class="k">skills:</span>
  <span class="c">-</span> conciliar-iva
  <span class="c">-</span> leer-factura
<span class="k">---</span>

<span class="p">## Reglas</span>
1. Nunca inventes un valor que no
   esté en el documento fuente.
2. Si falta un dato, pídelo.</pre>
        </div>
      </div>
    </div>
  </div>
</section>

<hr class="divider">

<!-- --------------------------------------------------------- Cómo funciona -->
<section class="section-sm">
  <div class="shell">
    <div class="bento">
      <?php
      $steps = [
          ['1', 'Elige un agente', 'Cada agente trae su archivo de reglas en Markdown y un conjunto de habilidades ya probadas.', 'agent'],
          ['2', 'Quita lo que sobra', 'Desmarca las habilidades que no vas a usar. El manifiesto se recalcula solo.', 'layers'],
          ['3', 'Descarga y carga', 'Un ZIP con AGENT.md y una habilidad por archivo .json. Se instala tal cual.', 'download'],
      ];
      foreach ($steps as $i => [$n, $title, $text, $ico]): ?>
        <div class="shellbox tight reveal" data-d="<?= $i + 1 ?>">
          <div class="core pad">
            <div class="row-tight" style="justify-content:space-between">
              <span class="card-glyph" aria-hidden="true"><?= icon($ico, 18) ?></span>
              <span class="mono faint" style="font-size:2rem;line-height:1;letter-spacing:-.05em"><?= $n ?></span>
            </div>
            <h2 style="margin-top:1.2rem;font-size:1.1rem;letter-spacing:-.025em"><?= e($title) ?></h2>
            <p class="text-sm muted mt-1"><?= e($text) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ----------------------------------------------------- Agentes destacados -->
<section class="section">
  <div class="shell">
    <div class="row" style="align-items:flex-end;margin-bottom:2.2rem">
      <div class="section-head" style="margin-bottom:0">
        <span class="eyebrow"><span class="dot"></span>En la tienda</span>
        <h2 style="margin-top:1.2rem">Agentes que ya hacen el trabajo</h2>
        <p class="muted mt-1">Cada uno llega con sus reglas escritas y sus habilidades enlazadas.</p>
      </div>
      <a class="btn btn-ghost push" href="<?= url('/agents') ?>">Ver todos <?= btnIcon('arrow') ?></a>
    </div>

    <?php if ($agents): ?>
      <div class="grid">
        <?php foreach ($agents as $i => $agent): ?>
          <?= View::partial('partials/agent-card', ['agent' => $agent, 'delay' => min(4, $i + 1)]) ?>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="shellbox">
        <div class="core empty">
          <span class="glyph"><?= icon('agent', 22) ?></span>
          <h3>Todavía no hay agentes publicados</h3>
          <p>En cuanto se publique el primero aparecerá aquí con sus reglas y habilidades.</p>
          <a class="btn btn-primary btn-sm mt-1" href="<?= url('/submit') ?>">Enviar el primero <?= btnIcon('arrow-up-right') ?></a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- -------------------------------------------- Habilidades sueltas + formatos -->
<section class="section-sm">
  <div class="shell">
    <div class="bento">
      <div class="shellbox w8 reveal">
        <div class="core pad-lg">
          <span class="eyebrow"><span class="dot"></span>Dos archivos, cero fricción</span>
          <h2 style="margin-top:1.3rem;font-size:clamp(1.5rem,3vw,2.1rem)">
            Las reglas se leen. Las habilidades se ejecutan.
          </h2>
          <p class="muted mt-2" style="max-width:52ch">
            Separamos las dos cosas a propósito. El archivo de reglas es texto que cualquier
            modelo entiende. Las habilidades son JSON con objetivo, entradas, salidas y ejemplos,
            para que el agente sepa exactamente qué puede hacer.
          </p>

          <div class="format-list mt-3" style="max-width:520px">
            <?php
            $formats = [
                ['.md',   'AGENT.md · SKILL.md', 'Las reglas y el prompt, en Markdown con front-matter.', ''],
                ['.json', 'skill.json',          'La habilidad estructurada: objetivo, workflow, inputs y outputs.', 'sky'],
                ['.txt',  'prompt.txt',          'Texto plano, para pegar en cualquier chat.', ''],
                ['.zip',  'paquete completo',    'Reglas + una habilidad por archivo + manifiesto.', ''],
            ];
            foreach ($formats as [$ext, $title, $desc, $tone]): ?>
              <div class="format-item">
                <span class="ext <?= e($tone) ?>"><?= e($ext) ?></span>
                <span>
                  <span class="t"><?= e($title) ?></span>
                  <span class="d"><?= e($desc) ?></span>
                </span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="shellbox reveal" data-d="2">
        <div class="core pad-lg" style="display:flex;flex-direction:column;height:100%">
          <span class="eyebrow"><span class="dot"></span>Categorías</span>
          <div class="chips mt-2" style="gap:.45rem">
            <?php foreach (array_slice($categories, 0, 10) as $cat): ?>
              <a class="badge" href="<?= url('/skills') ?>?category=<?= e($cat['slug']) ?>">
                <?= e($cat['name']) ?>
                <span class="faint"><?= (int) $cat['skills_count'] + (int) $cat['agents_count'] ?></span>
              </a>
            <?php endforeach; ?>
            <?php if (!$categories): ?>
              <span class="muted text-sm">Sin categorías todavía.</span>
            <?php endif; ?>
          </div>
          <a class="btn btn-ghost btn-sm mt-3" href="<?= url('/categories') ?>" style="margin-top:auto;align-self:flex-start">
            Ver todas <?= btnIcon('arrow') ?>
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ---------------------------------------------------- Habilidades recientes -->
<?php if ($skills): ?>
<section class="section">
  <div class="shell">
    <div class="row" style="align-items:flex-end;margin-bottom:2.2rem">
      <div class="section-head" style="margin-bottom:0">
        <span class="eyebrow"><span class="dot"></span>Biblioteca</span>
        <h2 style="margin-top:1.2rem">Habilidades sueltas</h2>
        <p class="muted mt-1">Añádelas a cualquier agente que ya tengas montado.</p>
      </div>
      <a class="btn btn-ghost push" href="<?= url('/skills') ?>">Ver la biblioteca <?= btnIcon('arrow') ?></a>
    </div>

    <div class="grid">
      <?php foreach ($skills as $i => $skill): ?>
        <?= View::partial('partials/skill-card', ['skill' => $skill, 'delay' => min(4, ($i % 4) + 1)]) ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- -------------------------------------------------------------- Aportar -->
<section class="section-sm">
  <div class="shell">
    <div class="shellbox reveal">
      <div class="core pad-lg">
        <div class="hero-grid" style="align-items:center;gap:clamp(1.5rem,4vw,3rem)">
          <div>
            <span class="eyebrow"><span class="dot"></span>Aporta</span>
            <h2 style="margin-top:1.2rem;font-size:clamp(1.5rem,3vw,2.1rem)">
              ¿Tienes un agente que funciona bien?
            </h2>
            <p class="muted mt-2" style="max-width:50ch">
              Compártelo. No hace falta crear cuenta: basta un correo para avisarte del resultado.
              Revisamos todo antes de publicarlo, así que nada entra a la biblioteca sin pasar por
              una persona.
            </p>
            <div class="btn-row mt-3">
              <a class="btn btn-primary" href="<?= url('/submit') ?>">Enviar una skill <?= btnIcon('arrow-up-right') ?></a>
              <a class="btn btn-ghost" href="<?= url('/register') ?>">Crear cuenta</a>
            </div>
          </div>

          <div class="stack-sm">
            <?php
            $bullets = [
                ['upload',  'Subes .md, .txt, .json o .zip'],
                ['mail',    'Te avisamos por correo del resultado'],
                ['shield',  'Nada se publica automáticamente'],
                ['link',    'Al aprobarse obtiene enlace público'],
            ];
            foreach ($bullets as [$ico, $text]): ?>
              <div class="format-item">
                <span class="ext"><?= icon($ico, 14) ?></span>
                <span class="t"><?= e($text) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?= View::partial('partials/cart-bar') ?>
