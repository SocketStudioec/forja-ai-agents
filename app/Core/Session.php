<?php
declare(strict_types=1);

namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        session_name('ssaisklib');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => Config::basePath() ?: '/',
            'domain'   => '',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        if (!isset($_SESSION['__started'])) {
            $_SESSION['__started'] = time();
            session_regenerate_id(true);
        }
    }

    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool) $p['secure'], (bool) $p['httponly']);
        }
        session_destroy();
    }

    /** Mensaje efímero de un solo uso. */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['__flash'][] = ['type' => $type, 'message' => $message];
    }

    /** @return array<int,array{type:string,message:string}> */
    public static function takeFlashes(): array
    {
        $f = $_SESSION['__flash'] ?? [];
        unset($_SESSION['__flash']);
        return $f;
    }

    /** Guarda los datos del formulario para repoblarlo tras un error. */
    public static function flashInput(array $input): void
    {
        unset($input['password'], $input['password_confirm'], $input['csrf_token']);
        $_SESSION['__old'] = $input;
    }

    public static function oldInput(): array
    {
        $o = $_SESSION['__old'] ?? [];
        unset($_SESSION['__old']);
        return $o;
    }
}
