<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Str;

final class Category
{
    /** @return array<int,array<string,mixed>> */
    public static function active(): array
    {
        return Database::all("SELECT * FROM categories WHERE status = 'active' ORDER BY position ASC, name ASC");
    }

    /** @return array<int,array<string,mixed>> */
    public static function all(): array
    {
        return Database::all('SELECT * FROM categories ORDER BY position ASC, name ASC');
    }

    /** Categorías con el número de recursos publicados en cada una. */
    public static function withCounts(): array
    {
        return Database::all(
            "SELECT c.*,
                (SELECT COUNT(*) FROM skills s WHERE s.category_id = c.id
                   AND s.status = 'published' AND s.visibility IN ('public','unlisted')) AS skills_count,
                (SELECT COUNT(DISTINCT a.id) FROM agents a
                   JOIN agent_categories ac ON ac.agent_id = a.id
                  WHERE ac.category_id = c.id
                    AND a.status = 'published' AND a.visibility IN ('public','unlisted')) AS agents_count
             FROM categories c
             WHERE c.status = 'active'
             ORDER BY c.position ASC, c.name ASC"
        );
    }

    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM categories WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::first('SELECT * FROM categories WHERE slug = :s LIMIT 1', ['s' => $slug]);
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name, 100);
        $slug = $base;
        $i    = 2;
        while (true) {
            $sql    = 'SELECT id FROM categories WHERE slug = :slug';
            $params = ['slug' => $slug];
            if ($ignoreId !== null) {
                $sql .= ' AND id <> :id';
                $params['id'] = $ignoreId;
            }
            if (Database::first($sql . ' LIMIT 1', $params) === null) {
                return $slug;
            }
            $slug = $base . '-' . $i++;
        }
    }

    /** @return array<int,string> id => nombre */
    public static function options(): array
    {
        $out = [];
        foreach (self::active() as $c) {
            $out[(int) $c['id']] = (string) $c['name'];
        }
        return $out;
    }
}
