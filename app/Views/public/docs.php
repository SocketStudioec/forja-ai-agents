<?php
/** @var array $compat */
use App\Core\Config;

$skillExample = <<<'MD'
---
name: conciliar-iva
title: Conciliar IVA mensual
description: Cruza el libro de compras con el ATS y reporta diferencias.
version: 1.2.0
author: Equipo contable
compatibility:
  - OpenClaw
---

# Conciliar IVA mensual

## Objetivo
Detectar diferencias entre el libro de compras y el anexo transaccional
antes de presentar la declaración.

## Instrucciones
Recibe dos archivos y compara factura por factura. Reporta sólo las
diferencias, nunca un resumen optimista.

## Workflow
1. Normaliza los RUC a 13 dígitos.
2. Cruza por número de autorización.
3. Agrupa las diferencias por tipo.
4. Devuelve una tabla y un total.

## Reglas
- Nunca inventes un valor que no esté en el documento fuente.
- Si falta un dato obligatorio, detente y pídelo.

## Inputs
- libro_compras: CSV o XLSX
- ats: XML del anexo

## Outputs
- tabla de diferencias
- total descuadrado
MD;

$jsonExample = <<<'JSON'
{
  "schema": "forja/skill@1",
  "name": "conciliar-iva",
  "title": "Conciliar IVA mensual",
  "version": "1.2.0",
  "compatibility": ["OpenClaw"],
  "objective": "Detectar diferencias entre el libro de compras y el anexo...",
  "workflow": [
    "Normaliza los RUC a 13 dígitos.",
    "Cruza por número de autorización.",
    "Agrupa las diferencias por tipo."
  ],
  "rules": [
    "Nunca inventes un valor que no esté en el documento fuente.",
    "Si falta un dato obligatorio, detente y pídelo."
  ],
  "inputs":  ["libro_compras: CSV o XLSX", "ats: XML del anexo"],
  "outputs": ["tabla de diferencias", "total descuadrado"]
}
JSON;

$tree = <<<'TXT'
analista-tributario/
├── AGENT.md          ← las reglas del agente
├── agent.json        ← manifiesto con la lista de habilidades
├── README.md
├── manifest.json
└── skills/
    ├── conciliar-iva.json
    ├── conciliar-iva.md
    ├── leer-factura.json
    └── leer-factura.md
TXT;
?>
<section class="section-sm">
  <div class="shell">
    <div class="section-head">
      <span class="eyebrow"><span class="dot"></span>Documentación</span>
      <h1 style="font-size:clamp(2rem,4.6vw,3.2rem);margin-top:1.2rem">Cómo usar esto con OpenClaw</h1>
      <p class="lede mt-2">
        Dos piezas y nada más: un archivo de reglas en Markdown y una habilidad por archivo JSON.
        Funciona igual en cualquier agente que sepa leer texto.
      </p>
    </div>

    <div class="detail-grid">
      <div class="prose">
        <h2 id="s-que-es">Qué es una skill</h2>
        <p>
          Una <strong>skill</strong> es una capacidad concreta y acotada: conciliar un IVA, redactar una
          respuesta de soporte, revisar un contrato. No es un agente completo, es una cosa que el agente
          sabe hacer bien. Se describe una vez y se reutiliza en todos los agentes que la necesiten.
        </p>
        <p>
          Un <strong>agente</strong> es la suma de un rol, unas reglas de comportamiento y un conjunto de
          skills. Las reglas le dicen cómo trabajar; las skills, qué puede hacer.
        </p>

        <h2 id="s-estructura">Estructura de SKILL.md</h2>
        <p>
          El archivo empieza con front-matter YAML entre <code>---</code> y sigue con secciones
          normales de Markdown. Los títulos de nivel dos son los que se convierten en campos del JSON,
          así que conviene respetarlos.
        </p>
        <pre class="md-pre"><code><?= e($skillExample) ?></code></pre>

        <h2 id="s-json">La misma skill en JSON</h2>
        <p>
          Al publicar, el Markdown se convierte en una definición estructurada. No se altera ni una
          instrucción: sólo se reorganiza para que el agente pueda leerla campo a campo.
        </p>
        <pre class="md-pre"><code><?= e($jsonExample) ?></code></pre>

        <h2 id="s-descarga">Cómo descargar</h2>
        <ol>
          <li>Abre la ficha del agente o de la skill.</li>
          <li>En el panel lateral elige el formato: <code>.md</code>, <code>.json</code>, <code>.txt</code> o <code>.zip</code>.</li>
          <li>En un agente puedes desmarcar las habilidades que no vayas a usar antes de bajar el ZIP.</li>
        </ol>
        <p>
          No hace falta iniciar sesión para descargar contenido público.
        </p>

        <h2 id="s-instalar">Cómo instalarlo</h2>
        <p>Dentro del ZIP de un agente encontrarás esto:</p>
        <pre class="md-pre"><code><?= e($tree) ?></code></pre>
        <ol>
          <li>Descomprime la carpeta dentro de tu proyecto.</li>
          <li>Carga <code>AGENT.md</code> como reglas o prompt de sistema del agente.</li>
          <li>Registra los archivos de <code>skills/</code> como habilidades disponibles.</li>
          <li>Si quitaste habilidades, <code>manifest.json</code> ya viene ajustado a tu selección.</li>
        </ol>

        <h2 id="s-otros">Otros agentes</h2>
        <p>
          El formato no está atado a OpenClaw. Hoy se marcan como compatibles:
          <?= e(implode(', ', $compat)) ?>. Si tu agente lee texto, el <code>.md</code> le sirve;
          si acepta herramientas declaradas, usa el <code>.json</code>.
        </p>

        <h2 id="s-practicas">Buenas prácticas</h2>
        <ul>
          <li><strong>Una skill, una responsabilidad.</strong> Si necesita tres párrafos de excepciones, probablemente son dos skills.</li>
          <li><strong>Escribe las reglas en negativo también.</strong> Lo que el agente no debe hacer evita más errores que lo que sí.</li>
          <li><strong>Declara entradas y salidas.</strong> Sin eso el agente improvisa el formato.</li>
          <li><strong>Versiona de verdad.</strong> Cambio de comportamiento es mayor, matiz es menor, corrección es parche.</li>
          <li><strong>Incluye un ejemplo real.</strong> Un caso concreto vale más que tres frases abstractas.</li>
        </ul>

        <h2 id="s-versionado">Versionado</h2>
        <p>
          Se usa <code>MAJOR.MINOR.PATCH</code>. Cambia el primer número cuando el comportamiento
          esperado deja de ser el mismo, el segundo cuando añades algo compatible y el tercero cuando
          corriges una errata o afinas una instrucción.
        </p>
      </div>

      <aside class="side-stack">
        <div class="shellbox tight">
          <div class="core pad">
            <h3 style="font-size:.95rem">En esta página</h3>
            <nav class="meta-list mt-1" aria-label="Índice">
              <?php
              $toc = [
                  's-que-es'     => 'Qué es una skill',
                  's-estructura' => 'Estructura de SKILL.md',
                  's-json'       => 'La misma skill en JSON',
                  's-descarga'   => 'Cómo descargar',
                  's-instalar'   => 'Cómo instalarlo',
                  's-otros'      => 'Otros agentes',
                  's-practicas'  => 'Buenas prácticas',
                  's-versionado' => 'Versionado',
              ];
              foreach ($toc as $id => $label): ?>
                <div><a class="k" href="#<?= e($id) ?>" style="color:var(--ink-mute)"><?= e($label) ?></a></div>
              <?php endforeach; ?>
            </nav>
          </div>
        </div>

        <div class="shellbox tight">
          <div class="core pad">
            <h3 style="font-size:.95rem">Empezar rápido</h3>
            <div class="btn-row mt-1">
              <a class="btn btn-primary btn-sm btn-block" href="<?= url('/agents') ?>">Ver agentes <?= btnIcon('arrow') ?></a>
            </div>
            <div class="btn-row mt-1">
              <a class="btn btn-ghost btn-sm btn-block" href="<?= url('/submit') ?>">Enviar una skill</a>
            </div>
          </div>
        </div>
      </aside>
    </div>
  </div>
</section>
