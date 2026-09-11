<?php
declare(strict_types=1);

namespace App\Core;

final class Config
{
    private static string $root = '';

    public static function boot(string $rootDir): void
    {
        self::$root = rtrim(str_replace('\\', '/', $rootDir), '/');
    }

    public static function root(): string
    {
        return self::$root;
    }

    public static function storagePath(string $sub = ''): string
    {
        return self::$root . '/storage' . ($sub !== '' ? '/' . ltrim($sub, '/') : '');
    }

    public static function viewPath(string $view): string
    {
        return self::$root . '/app/Views/' . ltrim($view, '/') . '.php';
    }

    /** Prefijo de la app dentro del dominio, p.ej. "/demo-aplicaciones/ai-skills". */
    public static function basePath(): string
    {
        $configured = Env::get('APP_BASE_PATH');
        if ($configured !== null && $configured !== '') {
            return '/' . trim($configured, '/');
        }
        return '';
    }

    public static function appUrl(): string
    {
        $url = Env::get('APP_URL');
        if ($url) {
            return rtrim($url, '/');
        }
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . self::basePath();
    }

    /** URL interna de la aplicación. */
    public static function url(string $path = '/'): string
    {
        $path = '/' . ltrim($path, '/');
        return self::basePath() . ($path === '/' ? '/' : rtrim($path, '/'));
    }

    /** URL absoluta (correos, metadatos, compartir). */
    public static function absUrl(string $path = '/'): string
    {
        $path = '/' . ltrim($path, '/');
        return self::appUrl() . ($path === '/' ? '/' : rtrim($path, '/'));
    }

    public static function asset(string $path): string
    {
        $file = self::$root . '/public/assets/' . ltrim($path, '/');
        $v    = is_file($file) ? (string) filemtime($file) : (Env::get('APP_VERSION') ?? '1');
        return self::basePath() . '/assets/' . ltrim($path, '/') . '?v=' . $v;
    }

    public static function appName(): string
    {
        return Env::get('APP_NAME', 'Forja') ?? 'Forja';
    }

    public static function isDebug(): bool
    {
        return Env::bool('APP_DEBUG', false);
    }

    /** @return array<int,string> */
    public static function compatibilityOptions(): array
    {
        $raw = Env::get('SKILL_COMPATIBILITY', 'OpenClaw,Claude,ChatGPT,Gemini,Otros');
        return array_values(array_filter(array_map('trim', explode(',', (string) $raw))));
    }

    /** @return array<int,string> */
    public static function formatOptions(): array
    {
        return ['md', 'txt', 'json', 'zip'];
    }

    public static function maxUploadBytes(): int
    {
        return Env::int('UPLOAD_MAX_BYTES', 5 * 1024 * 1024);
    }

    /** @return array<int,string> */
    public static function allowedUploadExtensions(): array
    {
        return ['md', 'txt', 'json', 'zip'];
    }
}
