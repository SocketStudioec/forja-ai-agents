<?php
declare(strict_types=1);

use App\Core\Config;

/** Escapa para HTML. Se usa en TODA salida que provenga de la base de datos. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Escapa para usarlo dentro de un atributo JSON de un data-*. */
function ejson($value): string
{
    return e((string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function url(string $path = '/'): string
{
    return Config::url($path);
}

function asset(string $path): string
{
    return Config::asset($path);
}

/** Construye una URL conservando los filtros actuales y sobrescribiendo algunos. */
function queryUrl(string $path, array $overrides = []): string
{
    $params = array_merge($_GET, $overrides);
    $params = array_filter($params, static fn ($v) => $v !== '' && $v !== null);
    unset($params['__r']);
    $qs = http_build_query($params);
    return Config::url($path) . ($qs !== '' ? '?' . $qs : '');
}

/** Marca "is-active" cuando la ruta actual coincide. */
function activeClass(string $prefix, string $class = 'is-active'): string
{
    $current = (string) (App\Core\Session::get('__path') ?? '/');
    if ($prefix === '/') {
        return $current === '/' ? ' ' . $class : '';
    }
    return strncmp($current, $prefix, strlen($prefix)) === 0 ? ' ' . $class : '';
}

/** Icono en línea del set propio (trazo fino, 1.5px). */
function icon(string $name, int $size = 16): string
{
    $paths = [
        'arrow'      => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-up-right' => '<path d="M7 17 17 7M8 7h9v9"/>',
        'download'   => '<path d="M12 3v12M7 11l5 5 5-5M4 21h16"/>',
        'search'     => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'plus'       => '<path d="M12 5v14M5 12h14"/>',
        'check'      => '<path d="m4 12.5 5 5L20 7"/>',
        'x'          => '<path d="M6 6l12 12M18 6 6 18"/>',
        'share'      => '<path d="M12 3v13M8 7l4-4 4 4"/><path d="M5 14v5a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-5"/>',
        'copy'       => '<rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/>',
        'star'       => '<path d="m12 4 2.4 5.1 5.6.7-4.1 3.9 1 5.5-4.9-2.7-4.9 2.7 1-5.5L4 9.8l5.6-.7z"/>',
        'agent'      => '<rect x="4" y="7" width="16" height="12" rx="3"/><path d="M12 3v4M9 13h.01M15 13h.01"/>',
        'skill'      => '<path d="M5 4h10l4 4v12H5z"/><path d="M14 4v5h5"/>',
        'grid'       => '<rect x="4" y="4" width="7" height="7" rx="2"/><rect x="13" y="4" width="7" height="7" rx="2"/><rect x="4" y="13" width="7" height="7" rx="2"/><rect x="13" y="13" width="7" height="7" rx="2"/>',
        'users'      => '<circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-5.5 6-5.5s6 2.2 6 5.5"/><path d="M16 5.5a3.2 3.2 0 0 1 0 6M18 20c0-2.4-.9-4-2.3-5"/>',
        'inbox'      => '<path d="M4 13h4l1.5 3h5L16 13h4"/><path d="M5.5 5h13l1.5 8v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-4z"/>',
        'chart'      => '<path d="M4 20V9M10 20V4M16 20v-7M22 20H2"/>',
        'tag'        => '<path d="M4 12V5a1 1 0 0 1 1-1h7l8 8-8 8z"/><circle cx="8.5" cy="8.5" r="1.2"/>',
        'shield'     => '<path d="M12 3 5 6v6c0 4.2 2.9 7.6 7 9 4.1-1.4 7-4.8 7-9V6z"/>',
        'doc'        => '<path d="M6 3h8l4 4v14H6z"/><path d="M13 3v5h5M9 13h6M9 17h6"/>',
        'settings'   => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M19.1 4.9 17 7M7 17l-2.1 2.1"/>',
        'logout'     => '<path d="M14 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4"/><path d="M10 16l-4-4 4-4M6 12h10"/>',
        'sun'        => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'moon'       => '<path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5z"/>',
        'upload'     => '<path d="M12 17V5M7 10l5-5 5 5M4 21h16"/>',
        'info'       => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
        'alert'      => '<path d="M12 4 2.5 20h19z"/><path d="M12 10v4M12 17h.01"/>',
        'trash'      => '<path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/>',
        'edit'       => '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="M14 5l4 4"/>',
        'eye'        => '<path d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6-10-6-10-6z"/><circle cx="12" cy="12" r="2.6"/>',
        'eye-off'    => '<path d="M4 4l16 16"/><path d="M9.5 9.6A2.6 2.6 0 0 0 12 14.6M6.3 6.6C3.8 8.3 2 12 2 12s3.6 6 10 6c1.8 0 3.3-.5 4.6-1.2M19.2 15.4C21 13.8 22 12 22 12s-3.6-6-10-6c-.8 0-1.6.1-2.3.3"/>',
        'cart'       => '<path d="M4 5h2l2 11h10l2-8H7"/><circle cx="10" cy="19" r="1.4"/><circle cx="17" cy="19" r="1.4"/>',
        'layers'     => '<path d="m12 3 8 4.5-8 4.5-8-4.5z"/><path d="m4 12 8 4.5 8-4.5"/><path d="m4 16.5 8 4.5 8-4.5"/>',
        'bolt'       => '<path d="M13 3 5 14h6l-1 7 8-11h-6z"/>',
        'book'       => '<path d="M5 4h9a3 3 0 0 1 3 3v13H8a3 3 0 0 1-3-3z"/><path d="M17 7h2v13H8"/>',
        'mail'       => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/>',
        'clock'      => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'history'    => '<path d="M4 12a8 8 0 1 0 2.5-5.8M4 4v4h4"/><path d="M12 8v4.5l3 1.8"/>',
        'link'       => '<path d="M10 13a4 4 0 0 0 5.7 0l2.6-2.6a4 4 0 0 0-5.7-5.7L11 6.4"/><path d="M14 11a4 4 0 0 0-5.7 0L5.7 13.6a4 4 0 0 0 5.7 5.7l1.6-1.6"/>',
        'filter'     => '<path d="M4 5h16l-6 7v6l-4 2v-8z"/>',
        'menu'       => '<path d="M4 7h16M4 12h16M4 17h16"/>',
    ];

    $d = $paths[$name] ?? $paths['info'];
    return '<svg viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" '
        . 'stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" '
        . 'aria-hidden="true" focusable="false">' . $d . '</svg>';
}

/** Botón con el icono anidado en su propio círculo. */
function btnIcon(string $name = 'arrow'): string
{
    return '<span class="btn-ico">' . icon($name, 13) . '</span>';
}

/** Valor previo del formulario tras un error de validación. */
function old(string $key, $default = '')
{
    static $data = null;
    if ($data === null) {
        $data = $GLOBALS['__old_input'] ?? [];
    }
    return $data[$key] ?? $default;
}

/** Devuelve el par de iniciales para un avatar textual. */
function glyphFor(string $text): string
{
    $words = preg_split('/\s+/', trim($text)) ?: [];
    if (count($words) >= 2) {
        return mb_strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1));
    }
    return mb_strtoupper(mb_substr($text, 0, 2));
}
