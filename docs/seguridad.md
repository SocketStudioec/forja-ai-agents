# Seguridad y privacidad

Qué está implementado, dónde vive y qué decisiones hay detrás.

---

## 1. Autenticación

| Control | Dónde | Nota |
| --- | --- | --- |
| Hash de contraseña | `app/Core/Auth.php`, `AuthController` | `password_hash` con `PASSWORD_DEFAULT` (bcrypt). Nunca se guarda ni se registra la contraseña en claro. |
| Rehash automático | `AuthController::login` | Si cambia el coste por defecto de PHP, la contraseña se vuelve a cifrar al entrar. |
| Respuesta de tiempo constante | `AuthController::login` | Si el usuario no existe se verifica igualmente contra un hash señuelo, para que la duración de la respuesta no revele qué correos están registrados. |
| Cuenta suspendida | `Auth::user()` | Se comprueba en cada petición: suspender a alguien corta su sesión activa, no sólo sus futuros accesos. |
| Recuperación | `AuthController::forgot` | Token de 32 bytes aleatorios; en base sólo se guarda su SHA-256. Caduca en una hora y se marca como usado. La respuesta es idéntica exista o no la cuenta. |

Requisito de contraseña: 10 caracteres mínimo combinando letras y números
(`Validator::password`).

---

## 2. Autorización

Toda comprobación ocurre en el servidor, antes de tocar datos.

- `Auth::requireLogin()` — exige sesión y recuerda el destino.
- `Auth::requireAdmin()` — exige rol `admin`; un intento fallido queda en la
  bitácora como `access_denied`.
- `Auth::requireOwnership($ownerId)` — un usuario sólo modifica lo suyo; un
  administrador pasa siempre.

El `DashboardController` llama a `requireLogin()` en su constructor y el
`AdminController` a `requireAdmin()`, así que ninguna acción de esas áreas puede
ejecutarse sin pasar por el filtro.

El contenido con `visibility` distinta de `public` sólo lo ve su autor o un
administrador, tanto en la ficha como en la descarga.

Verificado en producción: un usuario normal recibe **403** al pedir
`/admin`, `/admin/users`, la edición de contenido ajeno y su borrado por POST.

---

## 3. CSRF

`app/Core/Csrf.php`. Token de 32 bytes por sesión, insertado con `Csrf::field()`
en cada formulario y comparado con `hash_equals`. Un POST sin token o con token
inválido responde **419** y no ejecuta nada.

Cubre: acceso, registro, recuperación, creación y edición de contenido, cambios
de estado, borrados, favoritos, revisión de envíos, categorías y descarga del
paquete a medida.

---

## 4. Inyección SQL

`app/Core/Database.php` abre PDO con `ATTR_EMULATE_PREPARES => false`, es decir
sentencias preparadas nativas de MySQL. Ningún valor de usuario se concatena en
la consulta.

Sólo se interpolan directamente valores enteros generados por la propia
aplicación (`LIMIT`, `OFFSET`, ventanas de tiempo) y nombres de columna de listas
blancas cerradas.

> Cada marcador con nombre aparece **una sola vez** por consulta. Los prepares
> nativos no admiten reutilizar un nombre en dos posiciones, y hacerlo provoca
> `HY093`. Las búsquedas que comparan un mismo término contra varias columnas
> usan `:q1`, `:q2`… apuntando al mismo valor.

---

## 5. XSS

- `e()` (`htmlspecialchars` con `ENT_QUOTES`) envuelve toda salida que venga de
  la base de datos o del usuario.
- El renderizador Markdown (`app/Core/Markdown.php`) **escapa el documento
  entero antes** de aplicar formato, así que ni una etiqueta ni un `<script>`
  del contenido enviado por terceros puede sobrevivir.
- Los enlaces Markdown sólo admiten `http`, `https`, `mailto` y rutas relativas;
  cualquier otro esquema se degrada a texto plano.
- La política de contenido (`script-src 'self'`) bloquea scripts en línea. Por
  eso no hay ni un `onclick` en las plantillas y el arranque del tema vive en
  `public/assets/js/theme.js`.

---

## 6. Subida de archivos

`SubmissionController::handleUpload`:

1. Extensión en lista blanca: `md`, `txt`, `json`, `zip`.
2. Tamaño contra `UPLOAD_MAX_BYTES`.
3. Un `.json` que no parsea se rechaza.
4. Un archivo de texto que contiene bytes nulos se rechaza: no es texto.
5. Se guarda en `storage/uploads/AAAA/MM/` con **nombre aleatorio**; el nombre
   original sólo se conserva como dato para mostrar.
6. Permisos `0640`.

`storage/` está fuera de `public/` y además denegado por `.htaccess`. La descarga
del adjunto pasa por `AdminController::submissionFile`, que exige rol admin y
normaliza la ruta con `realpath` comprobando que siga dentro de `storage/`.

---

## 7. Abuso

`app/Core/RateLimit.php`, ventana deslizante por IP con sal:

| Acción | Límite |
| --- | --- |
| Acceso | 8 intentos / 15 min |
| Registro | 5 / hora |
| Recuperación | 5 / hora |
| Envío de skill | 5 / hora |

Los formularios públicos llevan además un campo trampa invisible: si viene
relleno, la petición se descarta en silencio.

---

## 8. Cabeceras

`app/bootstrap.php`:

```text
Content-Security-Policy: default-src 'self'; img-src 'self' data:;
  style-src 'self' 'unsafe-inline'; font-src 'self'; script-src 'self';
  connect-src 'self'; form-action 'self'; base-uri 'self';
  frame-ancestors 'self'; object-src 'none'
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()
```

`X-Powered-By` se elimina. No hay ningún recurso de terceros: las tipografías
están autoalojadas, precisamente para poder cerrar `font-src` a `'self'`.

`'unsafe-inline'` sigue permitido en `style-src` por los estilos en línea de las
plantillas. No debilita la protección contra scripts.

---

## 9. Auditoría

`audit_logs` guarda fecha, usuario, acción, tipo y id del recurso, metadatos y un
hash de la IP.

`Audit::log()` descarta cualquier clave de metadatos cuyo nombre contenga
`password`, `token`, `secret`, `api_key`, `csrf_token` o `authorization`, así que
un secreto no puede acabar en la bitácora por descuido. Un fallo al escribir la
bitácora nunca tumba la petición del usuario.

Se registran: altas y bajas de usuario, cambios de rol, accesos y fallos de
acceso, creación, edición, publicación y borrado de contenido, aprobaciones y
rechazos, descargas y accesos denegados.

---

## 10. Datos personales

| Dato | Dónde | Cómo se borra |
| --- | --- | --- |
| Cuenta (nombre, correo, usuario, hash) | `users` | El propio usuario desde `/dashboard/account`, o un administrador. |
| Envío sin cuenta (correo, nombre, archivo) | `skill_submissions` + `storage/` | El administrador desde la ficha del envío: borra la fila y el archivo del disco. |
| IP | `audit_logs`, `downloads`, `rate_limits` | Nunca se guarda en claro: sólo `sha256(APP_KEY + ip)`. |

Al borrar una cuenta, su contenido publicado sobrevive con `user_id` a `NULL`
(`ON DELETE SET NULL`). Es deliberado: los enlaces que ya se compartieron siguen
funcionando, pero no queda ningún dato personal asociado. Quien quiera borrar
también el contenido lo elimina antes desde su panel.

No se permite que quede la plataforma sin administradores: el último
administrador activo no puede suspenderse, cambiar de rol, borrarse ni darse de
baja a sí mismo.

---

## 11. Credenciales

Todo en variables de entorno (`.env`), fuera del repositorio y denegado por el
servidor web. `.gitignore` excluye `.env` y `.env.*` salvo `.env.example`.

En el código no hay ninguna contraseña, token ni clave. El instalador recibe la
contraseña del administrador por argumento o por teclado y sólo persiste su hash.

**Si una credencial se comparte alguna vez por un canal en claro —un chat, un
correo, una captura— hay que rotarla.** Estar en un gestor de secretos después
no deshace haber estado antes en un canal que no lo es.
