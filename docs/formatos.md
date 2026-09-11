# Formatos de agente y habilidad

Dos piezas, cada una con un trabajo distinto.

- Las **reglas** dicen *cómo* se comporta el agente. Van en Markdown porque las
  lee un modelo de lenguaje, y el texto plano es el formato que todos entienden.
- Las **habilidades** dicen *qué* puede hacer. Van en JSON porque las consume un
  programa que necesita campos, no prosa.

---

## AGENT.md

```markdown
---
name: analista-tributario
title: Analista tributario
role: Contabilidad · cierre mensual
description: Revisa comprobantes y concilia el IVA del periodo.
version: 1.0.0
author: Equipo editorial
updated: 2026-09-10
tags:
  - contabilidad
compatibility:
  - OpenClaw
skills:
  - conciliar-iva-mensual
  - leer-comprobante-electronico
source: https://…/agents/analista-tributario
---

# Analista tributario

Eres un analista tributario que trabaja el cierre mensual…

## Cómo trabajas
## Qué nunca haces
## Cuándo detenerte
## Cómo entregas
```

El front-matter lo genera la plataforma a partir de los metadatos del agente. El
cuerpo es exactamente lo que escribió el autor: no se reescribe, no se resume y
no se le añaden instrucciones.

Si el autor pega su propio front-matter en el cuerpo, se descarta el suyo y se
usa el generado, para que la versión y la lista de habilidades sean siempre las
que están publicadas.

---

## SKILL.md

Misma idea, con las secciones que luego se convierten en campos:

```markdown
## Objetivo        → objective   (texto)
## Instrucciones   → instructions(texto)
## Workflow        → workflow    (lista)
## Reglas          → rules       (lista)
## Inputs          → inputs      (lista)
## Outputs         → outputs     (lista)
## Ejemplos        → examples    (lista)
```

Se aceptan los equivalentes en inglés (`Objective`, `Instructions`, `Rules`,
`Workflow`, `Inputs`, `Outputs`, `Examples`) y algunas variantes en español
(`Propósito`, `Flujo`, `Flujo de trabajo`, `Proceso`, `Entradas`, `Salidas`,
`Restricciones`).

Para los campos de lista se extraen los elementos que empiezan por `-`, `*`, `+`
o por un número. Si la sección no tiene lista, el texto entero pasa como único
elemento.

---

## skill.json

```json
{
  "schema": "forja/skill@1",
  "name": "conciliar-iva-mensual",
  "title": "Conciliar IVA mensual",
  "description": "Cruza el libro de compras con el anexo…",
  "version": "1.0.0",
  "author": "Equipo editorial",
  "category": "Contabilidad y tributación",
  "tags": ["contabilidad", "impuestos"],
  "compatibility": ["OpenClaw", "Claude", "ChatGPT"],
  "updated_at": "2026-09-10T22:18:00-05:00",
  "source": "https://…/skills/conciliar-iva-mensual",
  "objective": "…",
  "instructions": "…",
  "workflow": ["…"],
  "rules": ["…"],
  "inputs": ["…"],
  "outputs": ["…"],
  "examples": ["…"],
  "prompt": "el cuerpo Markdown completo"
}
```

`prompt` lleva el cuerpo entero por si el agente prefiere recibir el texto tal
cual en lugar de los campos sueltos.

### JSON personalizado

En el editor hay un campo **JSON personalizado**. Lo que se escriba ahí se
fusiona **encima** de lo derivado, así que sólo hay que declarar lo que cambia:

```json
{ "tools": ["lector_pdf"], "max_tokens": 4000 }
```

Se valida al guardar: un JSON mal formado no se acepta.

---

## agent.json

```json
{
  "schema": "forja/agent@1",
  "name": "analista-tributario",
  "rules_file": "AGENT.md",
  "system_prompt": "…",
  "skills": [
    {
      "name": "conciliar-iva-mensual",
      "title": "Conciliar IVA mensual",
      "version": "1.0.0",
      "file": "skills/conciliar-iva-mensual.json",
      "source": "https://…/skills/conciliar-iva-mensual"
    }
  ]
}
```

Cuando el visitante desmarca habilidades en la ficha, el manifiesto del ZIP se
regenera con la selección. Las habilidades marcadas como **base** por el autor
entran siempre.

---

## Contenido del ZIP

### Un agente

```text
analista-tributario/
├── AGENT.md
├── agent.json
├── README.md
├── manifest.json
└── skills/
    ├── conciliar-iva-mensual.json
    ├── conciliar-iva-mensual.md
    └── …
```

### Una habilidad suelta

```text
conciliar-iva-mensual/
├── SKILL.md
├── skill.json
├── prompt.txt
├── README.md
└── metadata.json
```

### Un paquete a medida

```text
forja-pack/
├── pack.json
├── README.md
├── agents/<slug>/AGENT.md + agent.json + skills/
└── skills/<slug>.json + <slug>.md
```

---

## Versionado

`MAJOR.MINOR.PATCH`.

| Cambio | Sube |
| --- | --- |
| El agente deja de comportarse como antes | MAJOR |
| Se añade algo compatible | MINOR |
| Se corrige una errata o se afina una instrucción | PATCH |

El editor permite escribir la versión a mano o incrementarla con un desplegable.
Cada cambio de contenido queda registrado en `skill_versions` con quién lo hizo.

El **slug no cambia una vez publicado**: un enlace compartido nunca se rompe,
aunque se renombre el recurso.
