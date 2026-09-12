<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Str;

final class Agent
{
    public const SELECT = "a.*, c.name AS category_name, c.slug AS category_slug,
        COALESCE(NULLIF(TRIM(CONCAT(COALESCE(u.name,''),' ',COALESCE(u.lastname,''))),''),
                 u.username, a.author_name, 'Forja') AS author_display,
        u.username AS author_username,
        (SELECT COUNT(*) FROM agent_skills axs WHERE axs.agent_id = a.id) AS skills_count,
        (SELECT GROUP_CONCAT(c2.name ORDER BY ac2.is_primary DESC, c2.position ASC SEPARATOR '|')
           FROM agent_categories ac2 JOIN categories c2 ON c2.id = ac2.category_id
          WHERE ac2.agent_id = a.id) AS category_names,
        (SELECT GROUP_CONCAT(c3.slug ORDER BY ac3.is_primary DESC, c3.position ASC SEPARATOR '|')
           FROM agent_categories ac3 JOIN categories c3 ON c3.id = ac3.category_id
          WHERE ac3.agent_id = a.id) AS category_slugs";

    private const FROM = 'FROM agents a
        LEFT JOIN categories c ON c.id = a.category_id
        LEFT JOIN users u ON u.id = a.user_id';

    public static function publicWhere(string $alias = 'a'): string
    {
        return "{$alias}.status = 'published' AND {$alias}.visibility IN ('public','unlisted')";
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::first(
            'SELECT ' . self::SELECT . ' ' . self::FROM . ' WHERE a.slug = :slug LIMIT 1',
            ['slug' => $slug]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::first(
            'SELECT ' . self::SELECT . ' ' . self::FROM . ' WHERE a.id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public static function findManyByIds(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) {
            return [];
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        return Database::all(
            'SELECT ' . self::SELECT . ' ' . self::FROM
            . ' WHERE a.id IN (' . $in . ') AND ' . self::publicWhere()
            . ' ORDER BY a.name ASC',
            $ids
        );
    }

    /**
     * Habilidades enlazadas al agente. Sólo devuelve las publicadas salvo que
     * se pida lo contrario desde el panel de administración.
     * @return array<int,array<string,mixed>>
     */
    public static function skills(int $agentId, bool $onlyPublished = true): array
    {
        $sql = 'SELECT ' . Skill::SELECT . ', axs.required, axs.position
                FROM agent_skills axs
                JOIN skills s ON s.id = axs.skill_id
                LEFT JOIN categories c ON c.id = s.category_id
                LEFT JOIN users u ON u.id = s.user_id
                WHERE axs.agent_id = :id';
        if ($onlyPublished) {
            $sql .= " AND s.status = 'published' AND s.visibility IN ('public','unlisted')";
        }
        return Database::all($sql . ' ORDER BY axs.required DESC, axs.position ASC, s.name ASC', ['id' => $agentId]);
    }

    /** @return array<int,int> */
    public static function skillIds(int $agentId): array
    {
        $rows = Database::all('SELECT skill_id FROM agent_skills WHERE agent_id = :id', ['id' => $agentId]);
        return array_map(static fn ($r) => (int) $r['skill_id'], $rows);
    }

    /**
     * Reemplaza el conjunto de habilidades del agente.
     * @param array<int,int> $skillIds
     * @param array<int,int> $requiredIds
     */
    public static function syncSkills(int $agentId, array $skillIds, array $requiredIds = []): void
    {
        Database::run('DELETE FROM agent_skills WHERE agent_id = :id', ['id' => $agentId]);
        $pos = 0;
        foreach (array_unique(array_map('intval', $skillIds)) as $sid) {
            if ($sid <= 0) {
                continue;
            }
            Database::insert('agent_skills', [
                'agent_id' => $agentId,
                'skill_id' => $sid,
                'required' => in_array($sid, array_map('intval', $requiredIds), true) ? 1 : 0,
                'position' => $pos++,
            ]);
        }
    }

    /** @return array{items:array<int,array<string,mixed>>,total:int,pages:int,page:int} */
    public static function browse(array $f): array
    {
        $where  = [self::publicWhere()];
        $params = [];

        if (!empty($f['q'])) {
            $where[] = '(a.name LIKE :q1 OR a.role_title LIKE :q2 OR a.short_description LIKE :q3 OR a.tags LIKE :q4)';
            $like = '%' . $f['q'] . '%';
            $params['q1'] = $params['q2'] = $params['q3'] = $params['q4'] = $like;
        }
        if (!empty($f['category'])) {
            // Un agente aparece bajo cualquiera de sus categorías, no sólo
            // bajo la principal.
            $where[] = 'EXISTS (SELECT 1 FROM agent_categories acf
                                  JOIN categories ccf ON ccf.id = acf.category_id
                                 WHERE acf.agent_id = a.id AND ccf.slug = :cat)';
            $params['cat'] = $f['category'];
        }
        if (!empty($f['compat'])) {
            $where[] = 'a.compatibility LIKE :compat';
            $params['compat'] = '%' . $f['compat'] . '%';
        }
        if (!empty($f['tier'])) {
            $where[] = 'a.tier = :tier';
            $params['tier'] = $f['tier'];
        }

        $orderMap = [
            'downloads' => 'a.downloads DESC, a.published_at DESC',
            'recent'    => 'a.published_at DESC, a.id DESC',
            'name'      => 'a.name ASC',
            'skills'    => 'skills_count DESC, a.downloads DESC',
        ];
        $order = $orderMap[$f['sort'] ?? 'recent'] ?? $orderMap['recent'];

        $page    = max(1, (int) ($f['page'] ?? 1));
        $perPage = min(36, max(6, (int) ($f['per_page'] ?? 12)));
        $offset  = ($page - 1) * $perPage;
        $sqlWhere = ' WHERE ' . implode(' AND ', $where);

        $total = (int) Database::scalar('SELECT COUNT(*) ' . self::FROM . $sqlWhere, $params);
        $items = Database::all(
            'SELECT ' . self::SELECT . ' ' . self::FROM . $sqlWhere
            . ' ORDER BY ' . $order . ' LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $params
        );

        return ['items' => $items, 'total' => $total, 'pages' => (int) max(1, ceil($total / $perPage)), 'page' => $page];
    }

    /** @return array<int,array<string,mixed>> */
    public static function featured(int $limit = 4): array
    {
        return Database::all(
            'SELECT ' . self::SELECT . ' ' . self::FROM
            . ' WHERE ' . self::publicWhere() . " AND a.visibility = 'public'"
            . ' ORDER BY a.featured DESC, a.downloads DESC, a.published_at DESC LIMIT ' . max(1, $limit)
        );
    }

    /** @return array<int,array<string,mixed>> */
    public static function forUser(int $userId): array
    {
        return Database::all(
            'SELECT ' . self::SELECT . ' ' . self::FROM . ' WHERE a.user_id = :uid ORDER BY a.updated_at DESC',
            ['uid' => $userId]
        );
    }

    public static function adminList(array $f): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($f['status']))     { $where[] = 'a.status = :st';       $params['st']  = $f['status']; }
        if (!empty($f['visibility'])) { $where[] = 'a.visibility = :vis';  $params['vis'] = $f['visibility']; }
        if (!empty($f['tier']))       { $where[] = 'a.tier = :tier';       $params['tier'] = $f['tier']; }
        if (!empty($f['category'])) {
            $where[] = 'EXISTS (SELECT 1 FROM agent_categories acf
                                  JOIN categories ccf ON ccf.id = acf.category_id
                                 WHERE acf.agent_id = a.id AND ccf.slug = :cat)';
            $params['cat'] = $f['category'];
        }
        if (!empty($f['q'])) {
            $where[] = '(a.name LIKE :q1 OR a.slug LIKE :q2 OR u.email LIKE :q3 OR a.author_email LIKE :q4)';
            $like = '%' . $f['q'] . '%';
            $params['q1'] = $params['q2'] = $params['q3'] = $params['q4'] = $like;
        }

        $page     = max(1, (int) ($f['page'] ?? 1));
        $perPage  = 25;
        $offset   = ($page - 1) * $perPage;
        $sqlWhere = ' WHERE ' . implode(' AND ', $where);

        $total = (int) Database::scalar('SELECT COUNT(*) ' . self::FROM . $sqlWhere, $params);
        $items = Database::all(
            'SELECT ' . self::SELECT . ' ' . self::FROM . $sqlWhere
            . ' ORDER BY a.updated_at DESC LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $params
        );

        return ['items' => $items, 'total' => $total, 'pages' => (int) max(1, ceil($total / $perPage)), 'page' => $page];
    }

    /** @return array<int,int> ids de todas las categorías del agente */
    public static function categoryIds(int $agentId): array
    {
        $rows = Database::all(
            'SELECT ac.category_id FROM agent_categories ac
             JOIN categories c ON c.id = ac.category_id
             WHERE ac.agent_id = :id
             ORDER BY ac.is_primary DESC, c.position ASC',
            ['id' => $agentId]
        );
        return array_map(static fn ($r) => (int) $r['category_id'], $rows);
    }

    /**
     * Reemplaza el conjunto de categorías. La primera de la lista, en orden de
     * posición, queda como principal y se refleja en `agents.category_id` para
     * que la insignia y los listados sigan teniendo una sola categoría visible.
     *
     * @param array<int,int> $categoryIds
     */
    public static function syncCategories(int $agentId, array $categoryIds): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));

        Database::run('DELETE FROM agent_categories WHERE agent_id = :id', ['id' => $agentId]);

        if (!$ids) {
            Database::run('UPDATE agents SET category_id = NULL WHERE id = :id', ['id' => $agentId]);
            return;
        }

        $in    = implode(',', array_fill(0, count($ids), '?'));
        $orden = Database::all(
            'SELECT id FROM categories WHERE id IN (' . $in . ') ORDER BY position ASC, name ASC',
            $ids
        );
        $orden = array_map(static fn ($r) => (int) $r['id'], $orden);
        if (!$orden) {
            return;
        }

        $principal = $orden[0];
        foreach ($orden as $cid) {
            Database::insert('agent_categories', [
                'agent_id'    => $agentId,
                'category_id' => $cid,
                'is_primary'  => $cid === $principal ? 1 : 0,
            ]);
        }
        Database::run('UPDATE agents SET category_id = :c WHERE id = :id', ['c' => $principal, 'id' => $agentId]);
    }

    /** @return array<int,string> nombres, la principal primero */
    public static function categoryNames(array $agent): array
    {
        $raw = (string) ($agent['category_names'] ?? '');
        if ($raw === '') {
            return !empty($agent['category_name']) ? [(string) $agent['category_name']] : [];
        }
        return array_values(array_filter(explode('|', $raw)));
    }

    /** @return array<int,array{name:string,slug:string}> */
    public static function categoryPairs(array $agent): array
    {
        $nombres = self::categoryNames($agent);
        $slugs   = array_values(array_filter(explode('|', (string) ($agent['category_slugs'] ?? ''))));
        $out     = [];
        foreach ($nombres as $i => $n) {
            $out[] = ['name' => $n, 'slug' => $slugs[$i] ?? ($agent['category_slug'] ?? '')];
        }
        return $out;
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name, 150);
        $slug = $base;
        $i    = 2;
        while (true) {
            $sql    = 'SELECT id FROM agents WHERE slug = :slug';
            $params = ['slug' => $slug];
            if ($ignoreId !== null) {
                $sql .= ' AND id <> :id';
                $params['id'] = $ignoreId;
            }
            if (Database::first($sql . ' LIMIT 1', $params) === null) {
                return $slug;
            }
            $slug = $base . '-' . $i++;
            if ($i > 200) {
                return $base . '-' . bin2hex(random_bytes(3));
            }
        }
    }

    public static function registerDownload(int $agentId, ?int $userId, string $format): void
    {
        Database::insert('downloads', [
            'skill_id' => null,
            'agent_id' => $agentId,
            'user_id'  => $userId,
            'kind'     => 'agent',
            'format'   => $format,
            'ip_hash'  => Str::ipHash(),
        ]);
        Database::run('UPDATE agents SET downloads = downloads + 1 WHERE id = :id', ['id' => $agentId]);
    }
}
