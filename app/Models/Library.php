<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;

/**
 * La biblioteca personal: lo que cada usuario se ha guardado en su cuenta.
 *
 * Se consulta una sola vez por petición y se conserva en memoria, para que una
 * página con cuarenta tarjetas no lance cuarenta consultas preguntando lo mismo.
 */
final class Library
{
    /** @var array<int,int>|null */
    private static ?array $agents = null;
    /** @var array<int,int>|null */
    private static ?array $skills = null;

    /** @return array<int,int> */
    public static function agentIds(): array
    {
        if (self::$agents !== null) {
            return self::$agents;
        }
        $uid = Auth::id();
        if ($uid === null) {
            return self::$agents = [];
        }
        $rows = Database::all(
            'SELECT agent_id FROM favorites WHERE user_id = :u AND agent_id IS NOT NULL',
            ['u' => $uid]
        );
        return self::$agents = array_map(static fn ($r) => (int) $r['agent_id'], $rows);
    }

    /** @return array<int,int> */
    public static function skillIds(): array
    {
        if (self::$skills !== null) {
            return self::$skills;
        }
        $uid = Auth::id();
        if ($uid === null) {
            return self::$skills = [];
        }
        $rows = Database::all(
            'SELECT skill_id FROM favorites WHERE user_id = :u AND skill_id IS NOT NULL',
            ['u' => $uid]
        );
        return self::$skills = array_map(static fn ($r) => (int) $r['skill_id'], $rows);
    }

    public static function hasAgent(int $id): bool
    {
        return in_array($id, self::agentIds(), true);
    }

    public static function hasSkill(int $id): bool
    {
        return in_array($id, self::skillIds(), true);
    }

    public static function total(): int
    {
        return count(self::agentIds()) + count(self::skillIds());
    }

    /** Se llama tras guardar o quitar algo, para no servir un recuento viejo. */
    public static function forget(): void
    {
        self::$agents = null;
        self::$skills = null;
    }
}
