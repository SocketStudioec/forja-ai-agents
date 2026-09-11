# Base de datos

MySQL 8 / MariaDB 10.4, `utf8mb4_unicode_ci`, InnoDB. 14 tablas.
El esquema completo está en [`../database/schema.sql`](../database/schema.sql).

---

## Mapa

```text
users ──┬─< skills ──┬─< skill_formats
        │            ├─< skill_versions
        │            └─< agent_skills >── agents ──> users
        ├─< agents
        ├─< favorites >── skills / agents
        ├─< downloads >── skills / agents
        └─< audit_logs

categories ──< skills, agents, skill_submissions
skill_submissions ──> skills / agents   (al aprobarse)
password_resets · rate_limits · email_log   (sin relaciones)
```

---

## Tablas

### `users`
Cuentas. `role` es `admin` o `user`; `status` es `active`, `suspended` o
`pending_deletion`. `email` y `username` son únicos. Sólo se guarda
`password_hash`.

### `skills`
Habilidades. `description` es el cuerpo Markdown; `definition_json` es el JSON
opcional del autor que se fusiona sobre lo derivado. `status` cubre el ciclo
`draft → pending → under_review → approved → published → archived`, más
`rejected`. `visibility` es `public`, `unlisted` o `private`. `formats` lista
qué formatos se ofrecen. `author_name` y `author_email` sólo se rellenan cuando
la habilidad vino de un envío sin cuenta.

Índice `FULLTEXT` sobre nombre, descripciones y etiquetas, previsto para cuando
el catálogo crezca lo bastante como para que `LIKE` deje de bastar.

### `agents`
Agentes. `rules_md` es el archivo de reglas que se descarga tal cual.
`system_prompt` es la versión corta opcional. Mismo modelo de estados y
visibilidad que `skills`.

### `agent_skills`
Relación agente ↔ habilidad. `required = 1` marca las habilidades base, que el
visitante no puede desmarcar en la ficha. `position` fija el orden.

### `skill_formats`
Archivos materializados por formato. Hoy los formatos se generan al vuelo en
cada descarga, que para este volumen es más simple y siempre está al día. La
tabla existe para poder cachearlos en disco si el catálogo crece.

### `skill_versions`
Historial: versión, cuerpo, nota de cambio y quién editó.

### `skill_submissions`
Envíos sin cuenta. `reference` es el identificador público (`SK-20260910-A1B2C3`)
que recibe el remitente. `kind` distingue skill de agente. `content` guarda el
texto extraído del adjunto. `skill_id` / `agent_id` apuntan a lo creado al
aprobar. `consent` registra la autorización de tratamiento.

### `downloads`
Una fila por descarga: recurso, `kind` (`skill`, `agent`, `pack`), formato,
usuario si lo hay y hash de IP.

### `favorites`
Marcadores. Un único registro sirve para skills y agentes mediante dos columnas
nulables con restricción única por usuario.

### `audit_logs`
Bitácora. `metadata` es JSON filtrado: nunca contiene secretos.

### `password_resets`
Sólo el SHA-256 del token, con caducidad y marca de uso.

### `rate_limits`
Una fila por intento. Se purga sola: una de cada veinticinco peticiones borra lo
anterior a 24 horas.

### `email_log`
Trazabilidad de los correos: destinatario, plantilla, asunto, resultado y error.
No guarda el cuerpo.

---

## Decisiones

**`ON DELETE SET NULL` en el autor.** Borrar una cuenta no borra su contenido
publicado: los enlaces compartidos siguen vivos y el contenido queda anónimo.
Es lo que permite atender una supresión de datos sin romper la biblioteca.

**`ON DELETE CASCADE` en lo dependiente.** Formatos, versiones, relaciones,
descargas y favoritos desaparecen con su recurso.

**Categoría nulable.** Borrar una categoría no borra contenido: lo deja sin
clasificar.

**El slug es único y estable.** Una vez publicado no cambia aunque se renombre
el recurso, porque es la dirección pública que la gente ya compartió.
