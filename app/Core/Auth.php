<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Autenticación y autorización. Toda comprobación de permisos ocurre aquí, en
 * el servidor: el frontend sólo oculta enlaces, nunca autoriza.
 */
final class Auth
{
    /** @var array<string,mixed>|null */
    private static ?array $user = null;
    private static bool $resolved = false;

    /** @return array<string,mixed>|null */
    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$user;
        }
        self::$resolved = true;

        $id = Session::get('uid');
        if (!$id) {
            return self::$user = null;
        }

        $row = Database::first(
            'SELECT id, name, lastname, username, email, role, status, avatar, bio, created_at
             FROM users WHERE id = :id LIMIT 1',
            ['id' => (int) $id]
        );

        // Una cuenta suspendida pierde la sesión de inmediato.
        if ($row === null || $row['status'] !== 'active') {
            Session::forget('uid');
            return self::$user = null;
        }
        return self::$user = $row;
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int) $u['id'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function isAdmin(): bool
    {
        $u = self::user();
        return $u !== null && $u['role'] === 'admin';
    }

    public static function login(int $userId): void
    {
        session_regenerate_id(true);
        Session::set('uid', $userId);
        self::$resolved = false;
        self::$user     = null;
        Database::run('UPDATE users SET last_login_at = NOW() WHERE id = :id', ['id' => $userId]);
    }

    public static function logout(): void
    {
        Session::destroy();
        self::$user     = null;
        self::$resolved = true;
    }

    /** Exige sesión iniciada; si no, envía al login conservando el destino. */
    public static function requireLogin(): void
    {
        if (self::check()) {
            return;
        }
        Session::set('__intended', $_SERVER['REQUEST_URI'] ?? Config::url('/dashboard'));
        Session::flash('info', 'Inicia sesión para continuar.');
        Http::redirect('/login');
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (self::isAdmin()) {
            return;
        }
        Audit::log('access_denied', 'admin', null, ['path' => $_SERVER['REQUEST_URI'] ?? '']);
        http_response_code(403);
        View::render('errors/error', [
            'code'    => 403,
            'title'   => 'Acceso restringido',
            'message' => 'Esta sección es exclusiva de administradores.',
        ], 'Acceso restringido');
        exit;
    }

    /** ¿El usuario actual puede editar este recurso? */
    public static function ownsOrAdmin(?int $ownerId): bool
    {
        if (self::isAdmin()) {
            return true;
        }
        $me = self::id();
        return $me !== null && $ownerId !== null && $me === (int) $ownerId;
    }

    public static function requireOwnership(?int $ownerId): void
    {
        if (self::ownsOrAdmin($ownerId)) {
            return;
        }
        Audit::log('access_denied', 'resource', $ownerId, ['path' => $_SERVER['REQUEST_URI'] ?? '']);
        http_response_code(403);
        View::render('errors/error', [
            'code'    => 403,
            'title'   => 'No autorizado',
            'message' => 'Sólo puedes modificar contenido que te pertenece.',
        ], 'No autorizado');
        exit;
    }

    public static function displayName(array $user): string
    {
        $full = trim(($user['name'] ?? '') . ' ' . ($user['lastname'] ?? ''));
        return $full !== '' ? $full : (string) ($user['username'] ?? 'Usuario');
    }

    public static function initials(array $user): string
    {
        $n = trim((string) ($user['name'] ?? ''));
        $l = trim((string) ($user['lastname'] ?? ''));
        $a = $n !== '' ? mb_strtoupper(mb_substr($n, 0, 1)) : '';
        $b = $l !== '' ? mb_strtoupper(mb_substr($l, 0, 1)) : '';
        $r = $a . $b;
        return $r !== '' ? $r : mb_strtoupper(mb_substr((string) ($user['username'] ?? 'U'), 0, 2));
    }
}
