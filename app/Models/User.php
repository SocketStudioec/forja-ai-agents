<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Str;

final class User
{
    public static function findByEmail(string $email): ?array
    {
        return Database::first('SELECT * FROM users WHERE email = :e LIMIT 1', ['e' => mb_strtolower(trim($email))]);
    }

    public static function findByUsername(string $username): ?array
    {
        return Database::first('SELECT * FROM users WHERE username = :u LIMIT 1', ['u' => $username]);
    }

    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM users WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /** Identificador de acceso: acepta correo o nombre de usuario. */
    public static function findByLogin(string $login): ?array
    {
        // Cada marcador se nombra una sola vez: con prepares nativos MySQL no
        // admite reutilizar el mismo nombre en dos posiciones.
        $value = mb_strtolower(trim($login));
        return Database::first(
            'SELECT * FROM users WHERE email = :login_email OR username = :login_user LIMIT 1',
            ['login_email' => $value, 'login_user' => $value]
        );
    }

    public static function emailTaken(string $email, ?int $ignoreId = null): bool
    {
        $sql    = 'SELECT id FROM users WHERE email = :e';
        $params = ['e' => mb_strtolower(trim($email))];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $ignoreId;
        }
        return Database::first($sql . ' LIMIT 1', $params) !== null;
    }

    public static function usernameTaken(string $username, ?int $ignoreId = null): bool
    {
        $sql    = 'SELECT id FROM users WHERE username = :u';
        $params = ['u' => $username];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $ignoreId;
        }
        return Database::first($sql . ' LIMIT 1', $params) !== null;
    }

    public static function uniqueUsername(string $seed): string
    {
        $base = Str::slug($seed, 40) ?: 'usuario';
        $u    = $base;
        $i    = 2;
        while (self::usernameTaken($u)) {
            $u = $base . $i++;
            if ($i > 300) {
                return $base . bin2hex(random_bytes(3));
            }
        }
        return $u;
    }

    /** @return array{items:array<int,array<string,mixed>>,total:int,pages:int,page:int} */
    public static function adminList(array $f): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($f['role']))   { $where[] = 'u.role = :r';   $params['r'] = $f['role']; }
        if (!empty($f['status'])) { $where[] = 'u.status = :s'; $params['s'] = $f['status']; }
        if (!empty($f['q'])) {
            $where[] = '(u.name LIKE :q1 OR u.lastname LIKE :q2 OR u.username LIKE :q3 OR u.email LIKE :q4)';
            $like = '%' . $f['q'] . '%';
            $params['q1'] = $params['q2'] = $params['q3'] = $params['q4'] = $like;
        }

        $page     = max(1, (int) ($f['page'] ?? 1));
        $perPage  = 25;
        $offset   = ($page - 1) * $perPage;
        $sqlWhere = ' WHERE ' . implode(' AND ', $where);

        $total = (int) Database::scalar('SELECT COUNT(*) FROM users u' . $sqlWhere, $params);
        $items = Database::all(
            'SELECT u.*,
                (SELECT COUNT(*) FROM skills s WHERE s.user_id = u.id) AS skills_count,
                (SELECT COUNT(*) FROM agents a WHERE a.user_id = u.id) AS agents_count
             FROM users u' . $sqlWhere . '
             ORDER BY u.created_at DESC LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $params
        );

        return ['items' => $items, 'total' => $total, 'pages' => (int) max(1, ceil($total / $perPage)), 'page' => $page];
    }

    public static function statusLabel(string $s): string
    {
        return [
            'active'           => 'Activo',
            'suspended'        => 'Suspendido',
            'pending_deletion' => 'Baja solicitada',
        ][$s] ?? $s;
    }

    public static function roleLabel(string $r): string
    {
        return $r === 'admin' ? 'Administrador' : 'Usuario';
    }
}
