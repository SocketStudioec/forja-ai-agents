<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Str;

final class Skill
{
    public const SELECT = "s.*, c.name AS category_name, c.slug AS category_slug,
        COALESCE(NULLIF(TRIM(CONCAT(COALESCE(u.name,''),' ',COALESCE(u.lastname,''))),''),
                 u.username, s.author_name, 'Forja') AS author_display,
        u.username AS author_username";

    private const FROM = 'FROM skills s
        LEFT JOIN categories c ON c.id = s.category_id
        LEFT JOIN users u ON u.id = s.user_id';

    /** Estados que hacen visible una skill al público. */
    public static function publicWhere(string $alias = 's'): string
    {
        return "{$alias}.status = 'published' AND {$alias}.visibility IN ('public','unlisted')";
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::first(
            'SELECT ' . self::SELECT . ' ' . self::FROM . ' WHERE s.slug = :slug LIMIT 1',
            ['slug' => $slug]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::first(
            'SELECT ' . self::SELECT . ' ' . self::FROM . ' WHERE s.id = :id LIMIT 1',
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
            . ' WHERE s.id IN (' . $in . ') AND ' . self::publicWhere()
            . ' ORDER BY s.name ASC',
            $ids
        );
    }

    /**
     * Catálogo público con búsqueda, filtros y orden.
     * @param array<string,mixed> $f
     * @return array{items:array<int,array<string,mixed>>,total:int,pages:int,page:int}
     */
    public static function browse(array $f): array
    {
        $where  = [self::publicWhere()];
        $params = [];

        if (!empty($f['q'])) {
            $where[] = '(s.name LIKE :q1 OR s.short_description LIKE :q2 OR s.tags LIKE :q3)';
            $like = '%' . $f['q'] . '%';
            $params['q1'] = $params['q2'] = $params['q3'] = $like;
        }
        if (!empty($f['category'])) {
            $where[] = 'c.slug = :cat';
            $params['cat'] = $f['category'];
        }
        if (!empty($f['compat'])) {
            $where[] = 's.compatibility LIKE :compat';
            $params['compat'] = '%' . $f['compat'] . '%';
        }
        if (!empty($f['tag'])) {
            $where[] = 's.tags LIKE :tag';
            $params['tag'] = '%' . $f['tag'] . '%';
        }

        $orderMap = [
            'downloads' => 's.downloads DESC, s.published_at DESC',
            'recent'    => 's.published_at DESC, s.id DESC',
            'name'      => 's.name ASC',
            'featured'  => 's.featured DESC, s.downloads DESC',
        ];
        $order = $orderMap[$f['sort'] ?? 'recent'] ?? $orderMap['recent'];

        $page    = max(1, (int) ($f['page'] ?? 1));
        $perPage = min(48, max(6, (int) ($f['per_page'] ?? 12)));
        $offset  = ($page - 1) * $perPage;

        $sqlWhere = ' WHERE ' . implode(' AND ', $where);
        $total = (int) Database::scalar('SELECT COUNT(*) ' . self::FROM . $sqlWhere, $params);

        $items = Database::all(
            'SELECT ' . self::SELECT . ' ' . self::FROM . $sqlWhere
            . ' ORDER BY ' . $order . ' LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $params
        );

        return [
            'items' => $items,
            'total' => $total,
            'pages' => (int) max(1, ceil($total / $perPage)),
            'page'  => $page,
        ];
    }

    /** @return array<int,array<string,mixed>> */
    public static function featured(int $limit = 6): array
    {
        return Database::all(
            'SELECT ' . self::SELECT . ' ' . self::FROM
            . ' WHERE ' . self::publicWhere() . " AND s.visibility = 'public'"
            . ' ORDER BY s.featured DESC, s.downloads DESC, s.published_at DESC LIMIT ' . max(1, $limit)
        );
    }

    /** @return array<int,array<string,mixed>> */
    public static function forUser(int $userId, ?string $status = null): array
    {
        $sql    = 'SELECT ' . self::SELECT . ' ' . self::FROM . ' WHERE s.user_id = :uid';
        $params = ['uid' => $userId];
        if ($status !== null && $status !== '') {
            $sql .= ' AND s.status = :st';
            $params['st'] = $status;
        }
        return Database::all($sql . ' ORDER BY s.updated_at DESC', $params);
    }

    /** Listado del panel de administración con filtros cruzados. */
    public static function adminList(array $f): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($f['status'])) {
            $where[] = 's.status = :st';
            $params['st'] = $f['status'];
        }
        if (!empty($f['visibility'])) {
            $where[] = 's.visibility = :vis';
            $params['vis'] = $f['visibility'];
        }
        if (!empty($f['category'])) {
            $where[] = 'c.slug = :cat';
            $params['cat'] = $f['category'];
        }
        if (!empty($f['compat'])) {
            $where[] = 's.compatibility LIKE :compat';
            $params['compat'] = '%' . $f['compat'] . '%';
        }
        if (!empty($f['user'])) {
            $where[] = '(u.username = :usr1 OR u.email = :usr2)';
            $params['usr1'] = $params['usr2'] = $f['user'];
        }
        if (!empty($f['q'])) {
            $where[] = '(s.name LIKE :q1 OR s.slug LIKE :q2 OR s.author_email LIKE :q3 OR u.email LIKE :q4)';
            $like = '%' . $f['q'] . '%';
            $params['q1'] = $params['q2'] = $params['q3'] = $params['q4'] = $like;
        }

        $page    = max(1, (int) ($f['page'] ?? 1));
        $perPage = 25;
        $offset  = ($page - 1) * $perPage;
        $sqlWhere = ' WHERE ' . implode(' AND ', $where);

        $total = (int) Database::scalar('SELECT COUNT(*) ' . self::FROM . $sqlWhere, $params);
        $items = Database::all(
            'SELECT ' . self::SELECT . ' ' . self::FROM . $sqlWhere
            . ' ORDER BY s.updated_at DESC LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $params
        );

        return ['items' => $items, 'total' => $total, 'pages' => (int) max(1, ceil($total / $perPage)), 'page' => $page];
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name, 150);
        $slug = $base;
        $i    = 2;
        while (true) {
            $sql    = 'SELECT id FROM skills WHERE slug = :slug';
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

    public static function registerDownload(int $skillId, ?int $userId, string $format): void
    {
        Database::insert('downloads', [
            'skill_id' => $skillId,
            'agent_id' => null,
            'user_id'  => $userId,
            'kind'     => 'skill',
            'format'   => $format,
            'ip_hash'  => Str::ipHash(),
        ]);
        Database::run('UPDATE skills SET downloads = downloads + 1 WHERE id = :id', ['id' => $skillId]);
    }

    public static function recordVersion(int $skillId, string $version, ?string $description, ?string $changelog, ?int $editedBy): void
    {
        Database::insert('skill_versions', [
            'skill_id'    => $skillId,
            'version'     => $version,
            'description' => $description,
            'changelog'   => $changelog,
            'edited_by'   => $editedBy,
        ]);
    }

    /** @return array<int,array<string,mixed>> */
    public static function versions(int $skillId, int $limit = 20): array
    {
        return Database::all(
            'SELECT v.*, u.username AS editor
             FROM skill_versions v LEFT JOIN users u ON u.id = v.edited_by
             WHERE v.skill_id = :id ORDER BY v.id DESC LIMIT ' . max(1, $limit),
            ['id' => $skillId]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public static function related(array $skill, int $limit = 3): array
    {
        return Database::all(
            'SELECT ' . self::SELECT . ' ' . self::FROM
            . ' WHERE ' . self::publicWhere() . ' AND s.id <> :id'
            . ' AND (s.category_id = :cat OR s.compatibility LIKE :compat)'
            . ' ORDER BY s.downloads DESC LIMIT ' . max(1, $limit),
            [
                'id'     => (int) $skill['id'],
                'cat'    => $skill['category_id'],
                'compat' => '%' . (Str::listFromCsv($skill['compatibility'])[0] ?? 'OpenClaw') . '%',
            ]
        );
    }

    /** Agentes públicos que incluyen esta habilidad. */
    public static function usedByAgents(int $skillId, int $limit = 6): array
    {
        return Database::all(
            "SELECT a.id, a.name, a.slug, a.role_title, a.short_description
             FROM agent_skills asx
             JOIN agents a ON a.id = asx.agent_id
             WHERE asx.skill_id = :id AND a.status = 'published' AND a.visibility IN ('public','unlisted')
             ORDER BY a.downloads DESC LIMIT " . max(1, $limit),
            ['id' => $skillId]
        );
    }

    public static function statusLabel(string $status): string
    {
        return [
            'draft'        => 'Borrador',
            'pending'      => 'Pendiente',
            'under_review' => 'En revisión',
            'approved'     => 'Aprobada',
            'rejected'     => 'Rechazada',
            'published'    => 'Publicada',
            'archived'     => 'Archivada',
        ][$status] ?? $status;
    }
}
