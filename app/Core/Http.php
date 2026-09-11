<?php
declare(strict_types=1);

namespace App\Core;

final class Http
{
    public static function redirect(string $path, int $code = 302): void
    {
        $url = preg_match('#^https?://#i', $path) ? $path : Config::url($path);
        header('Location: ' . $url, true, $code);
        exit;
    }

    public static function back(string $fallback = '/'): void
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        // Sólo se acepta un referer del propio host: evita redirecciones abiertas.
        if ($ref !== '' && $host !== '' && stripos(parse_url($ref, PHP_URL_HOST) ?? '', $host) === 0) {
            header('Location: ' . $ref, true, 302);
            exit;
        }
        self::redirect($fallback);
    }

    public static function json($data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    public static function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return stripos($accept, 'application/json') !== false
            || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    public static function input(string $key, string $default = ''): string
    {
        $v = $_POST[$key] ?? $_GET[$key] ?? $default;
        if (is_array($v)) {
            return $default;
        }
        return trim((string) $v);
    }

    /** @return array<int,string> */
    public static function inputArray(string $key): array
    {
        $v = $_POST[$key] ?? $_GET[$key] ?? [];
        if (!is_array($v)) {
            return [];
        }
        return array_values(array_filter(array_map(
            static fn ($i) => is_scalar($i) ? trim((string) $i) : '',
            $v
        ), static fn ($i) => $i !== ''));
    }

    public static function inputInt(string $key, int $default = 0): int
    {
        $v = self::input($key, (string) $default);
        return is_numeric($v) ? (int) $v : $default;
    }

    public static function inputBool(string $key): bool
    {
        $v = self::input($key);
        return in_array(strtolower($v), ['1', 'on', 'true', 'yes'], true);
    }

    /** Cuerpo grande (contenido markdown) sin trim agresivo. */
    public static function inputRaw(string $key, string $default = ''): string
    {
        $v = $_POST[$key] ?? $default;
        if (is_array($v)) {
            return $default;
        }
        return str_replace(["\r\n", "\r"], "\n", (string) $v);
    }

    /** Fuerza la descarga de un archivo generado en memoria. */
    public static function download(string $filename, string $content, string $mime): void
    {
        // El nombre se limpia para que no pueda romper la cabecera.
        $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?? 'descarga';
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $safe . '"');
        header('Content-Length: ' . strlen($content));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=0, must-revalidate');
        echo $content;
        exit;
    }
}
