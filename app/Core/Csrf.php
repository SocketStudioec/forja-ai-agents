<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        $t = Session::get('__csrf');
        if (!is_string($t) || strlen($t) !== 64) {
            $t = bin2hex(random_bytes(32));
            Session::set('__csrf', $t);
        }
        return $t;
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . self::token() . '">';
    }

    public static function check(?string $token): bool
    {
        $stored = Session::get('__csrf');
        return is_string($stored) && is_string($token) && hash_equals($stored, $token);
    }

    /** Corta la petición si el token no es válido. */
    public static function verify(): void
    {
        if (self::check($_POST['csrf_token'] ?? null)) {
            return;
        }
        http_response_code(419);
        View::render('errors/error', [
            'code'    => 419,
            'title'   => 'Sesión expirada',
            'message' => 'Tu sesión caducó por seguridad. Vuelve a cargar la página e inténtalo de nuevo.',
        ], 'Sesión expirada');
        exit;
    }
}
