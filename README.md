# Forja — tienda de agentes de IA

Plataforma para publicar, descubrir y descargar **agentes de IA**. Cada agente
lleva sus **reglas en Markdown** (`AGENT.md`) y sus **habilidades en JSON**
(`skills/*.json`). El visitante elige qué agentes y qué habilidades se lleva, y
descarga un único ZIP que funciona tal cual.

- **Producción:** https://socket-studio.com/demo-aplicaciones/ai-skills/
- **Stack:** PHP 8.0 · MySQL 8 · Apache tras nginx · sin dependencias externas
- **Sin Composer, sin npm, sin framework.** Todo el código es de este repositorio.

---

## 1. Qué hace

| Rol | Puede |
| --- | --- |
| **Visitante** | Explorar, buscar y filtrar el catálogo. Ver cualquier ficha pública. Descargar en `.md`, `.txt`, `.json` y `.zip` sin registrarse. Armar un paquete a medida. Enviar una skill o un agente indicando sólo su correo. |
| **Usuario** | Todo lo anterior más: crear, editar, versionar, publicar y despublicar sus propias skills y agentes. Enlazar habilidades a un agente. Favoritos. Estadísticas de sus descargas. Editar su cuenta y darse de baja. |
| **Administrador** | Todo lo anterior más: ver y editar el contenido de cualquier usuario, aprobar y rechazar envíos con motivo, gestionar usuarios y roles, categorías, y consultar descargas y la bitácora de auditoría. |

Nada enviado por un visitante se publica solo: siempre queda en `pending` hasta
que una persona lo aprueba.

---

## 2. Estructura

```text
.
├── app/
│   ├── Controllers/     Controladores (uno por área funcional)
│   ├── Core/            Router, PDO, sesión, CSRF, auditoría, correo,
│   │                    Markdown, escritor ZIP y generador de formatos
│   ├── Models/          Consultas de dominio (Agent, Skill, User, …)
│   ├── Views/           Plantillas PHP: layouts, partials, público, panel, admin
│   ├── bootstrap.php    Autocarga, entorno, cabeceras de seguridad, sesión
│   └── helpers.php      e(), url(), asset(), icon(), …
├── public/              ÚNICA carpeta que sirve el servidor web
│   ├── assets/          css, js, fuentes autoalojadas, favicon
│   ├── index.php        Controlador frontal y tabla de rutas
│   └── .htaccess
├── database/schema.sql  Esquema completo (14 tablas)
├── scripts/
│   ├── install.php      Instalador por línea de comandos
│   └── seed_data.php    Categorías, 10 habilidades y 4 agentes de arranque
├── docs/                Documentación técnica
├── storage/             Adjuntos de los envíos (fuera del alcance web)
├── .env.example
└── .htaccess            Reescribe todo hacia public/
```

El servidor sólo debe poder servir `public/`. El `.htaccess` de la raíz reescribe
cualquier petición hacia ahí, y `app/`, `storage/`, `database/` y `scripts/`
tienen su propia denegación por si `mod_rewrite` faltara.

---

## 3. Instalación

### Requisitos

PHP 8.0 o superior con `pdo_mysql`, `mbstring`, `curl` y `zlib`.
MySQL 8 o MariaDB 10.4. Apache con `mod_rewrite` y `AllowOverride All`.

> La extensión `zip` **no** hace falta: el paquete ZIP se arma en PHP puro con
> `gzdeflate` (ver `app/Core/Zip.php`).

### Pasos

```bash
git clone <url-del-repositorio> forja
cd forja
cp .env.example .env
# Edita .env: base de datos, correo, APP_URL y APP_BASE_PATH
php -r "echo bin2hex(random_bytes(32));"   # valor para APP_KEY

php scripts/install.php --schema
php scripts/install.php --seed
php scripts/install.php --admin --email=tu@dominio.com --password='...'
```

Los tres pasos juntos: `php scripts/install.php --all --email=… --password=…`.

Si no pasas `--email` o `--password`, el instalador los pide por teclado. La
contraseña nunca se guarda: sólo su hash.

### Permisos

```bash
chown -R deploy:www-data .
chmod -R g+rX app public database scripts
chown -R www-data:www-data storage .env
chmod -R 770 storage
chmod 640 .env
```

`storage/` debe ser escribible por el usuario de PHP-FPM. Todo lo demás sólo
necesita lectura.

---

## 4. Variables de entorno

| Variable | Para qué |
| --- | --- |
| `APP_NAME` | Nombre de la marca. Cambia el rótulo en toda la interfaz y en los correos. |
| `APP_URL` | URL absoluta. Se usa en enlaces públicos, correos y metadatos. |
| `APP_BASE_PATH` | Prefijo dentro del dominio, p. ej. `/demo-aplicaciones/ai-skills`. Vacío si vive en la raíz. |
| `APP_KEY` | Sal para anonimizar las IP de la bitácora. Cadena aleatoria larga. |
| `APP_DEBUG` | `false` en producción. Con `true` se muestran las trazas. |
| `DB_*` | Conexión MySQL. |
| `MAIL_*` | Endpoint HTTP de correo, credenciales Basic y remitente. |
| `ADMIN_EMAIL` | Destinatario de los avisos de nuevos envíos. |
| `UPLOAD_MAX_BYTES` | Tamaño máximo de adjunto. |
| `SKILL_COMPATIBILITY` | Lista de plataformas ofrecidas como compatibilidad. |

`.env` está en `.gitignore` y el servidor lo deniega explícitamente.

---

## 5. Formatos

| Archivo | Qué es |
| --- | --- |
| `AGENT.md` | Reglas del agente. Front-matter YAML con versión y lista de habilidades, cuerpo Markdown intacto. |
| `agent.json` | Manifiesto: metadatos y rutas a las habilidades. |
| `SKILL.md` | Habilidad en Markdown con front-matter. |
| `skill.json` | La misma habilidad estructurada: `objective`, `instructions`, `workflow`, `rules`, `inputs`, `outputs`, `examples`, `prompt`. |
| `prompt.txt` | Texto plano, sin sintaxis Markdown. |
| `*.zip` | Paquete con todo lo anterior más `README.md` y `manifest.json`. |

El JSON se deriva del Markdown leyendo las secciones `## Objetivo`,
`## Instrucciones`, `## Workflow`, `## Reglas`, `## Inputs`, `## Outputs` y
`## Ejemplos`. El autor puede sobrescribir o añadir campos con un JSON propio
que se fusiona encima. La conversión nunca altera el texto de las instrucciones.

Detalles en [`docs/formatos.md`](docs/formatos.md).

Un agente puede pertenecer a **varias categorías** a la vez. La de posición más
alta queda como principal, que es la que aparece en la insignia y la que ordena;
el resto sirven para que el agente se encuentre por cualquiera de ellas en el
catálogo.

Las reglas y habilidades de marketing citan fuentes primarias en materia de
cumplimiento. De dónde sale cada una está en
[`docs/investigacion-marketing.md`](docs/investigacion-marketing.md).

### Plantillas de pago

Un agente o una habilidad puede marcarse como **de pago**. Entonces:

- Aparece en el catálogo con su nombre, sus categorías, qué incluye y, si se
  indicó, su precio.
- **No se descarga.** El bloqueo está en el controlador de descarga, que
  responde `402` a cualquier formato, no en ocultar el botón.
- **No se previsualiza su contenido**: ni las reglas, ni el manifiesto, ni los
  archivos de sus habilidades.
- **No entra en los paquetes del constructor**, aunque se fuerce su slug en el
  formulario.
- En su lugar se muestra un panel con el precio y un enlace de contacto.

Sólo un administrador puede marcarla. `Tier::resolve` ignora el campo del
formulario cuando quien envía no lo es, así que un usuario no puede ni crear una
de pago ni liberar una existente. Su autor y los administradores sí ven y
descargan el contenido, porque necesitan revisar lo que venden.

---

## 6. Seguridad

- Contraseñas con `password_hash` (bcrypt) y rehash automático al iniciar sesión.
- Sesiones con cookie `HttpOnly`, `SameSite=Lax`, `Secure` bajo HTTPS y
  regeneración de identificador al autenticarse.
- Token CSRF en cada formulario, verificado con `hash_equals`.
- Todas las consultas usan sentencias preparadas con marcadores únicos.
- Autorización comprobada en el servidor en cada acción. El frontend sólo oculta
  enlaces; no autoriza nada.
- Limitación por frecuencia en acceso, registro, recuperación y envíos.
- Trampa oculta para robots en los formularios públicos.
- Adjuntos validados por extensión, tamaño y contenido, guardados fuera del
  alcance web con nombre aleatorio y servidos sólo a administradores.
- Cabeceras `Content-Security-Policy`, `X-Content-Type-Options`, `X-Frame-Options`,
  `Referrer-Policy` y `Permissions-Policy`. No hay scripts ni recursos de terceros.
- La bitácora filtra cualquier clave que contenga `password`, `token`, `secret`
  o `api_key` antes de escribir.

Detalles en [`docs/seguridad.md`](docs/seguridad.md).

---

## 7. Privacidad

Se pide correo incluso a quien envía sin cuenta. La página `/privacy` explica qué
se recoge, para qué, cuánto se conserva y cómo pedir la supresión. Un usuario
registrado puede borrar su cuenta desde su panel: se eliminan sus datos
personales y su contenido publicado queda sin autor asociado, para no romper los
enlaces ya compartidos. Para envíos sin cuenta, el administrador elimina el
registro y el adjunto desde la ficha del envío.

Las direcciones IP nunca se guardan en claro: sólo un hash con sal.

---

## 8. Desarrollo

No hay proceso de compilación. Edita, sube y recarga.

```bash
php -S localhost:8000 -t public    # con APP_BASE_PATH vacío en .env
```

Comprobación de sintaxis de todo el proyecto:

```bash
find . -name '*.php' -exec php -l {} \; | grep -v 'No syntax errors'
```

---

## 9. Despliegue

```bash
tar -czf - --exclude='.git' . | ssh usuario@servidor 'cd /ruta/destino && tar -xzf -'
ssh usuario@servidor 'cd /ruta/destino && php scripts/install.php --schema'
```

`.env` se crea **en el servidor**, nunca se sube desde el repositorio.
Tras cada despliegue, revisa que `storage/` siga siendo escribible por PHP-FPM.

---

## 10. Licencia y créditos

Código propio, sin dependencias de terceros. Tipografías Plus Jakarta Sans y
JetBrains Mono, ambas bajo SIL Open Font License, autoalojadas en
`public/assets/fonts/`.
