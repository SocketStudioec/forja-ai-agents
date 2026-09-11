<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Submission
{
    private const SELECT = 'sub.*, c.name AS category_name,
        r.username AS reviewer_username';

    private const FROM = 'FROM skill_submissions sub
        LEFT JOIN categories c ON c.id = sub.category_id
        LEFT JOIN users r ON r.id = sub.reviewed_by';

    public static function find(int $id): ?array
    {
        return Database::first(
            'SELECT ' . self::SELECT . ' ' . self::FROM . ' WHERE sub.id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public static function findByReference(string $ref): ?array
    {
        return Database::first(
            'SELECT ' . self::SELECT . ' ' . self::FROM . ' WHERE sub.reference = :r LIMIT 1',
            ['r' => $ref]
        );
    }

    /** @return array{items:array<int,array<string,mixed>>,total:int,pages:int,page:int} */
    public static function adminList(array $f): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($f['status'])) { $where[] = 'sub.status = :st'; $params['st'] = $f['status']; }
        if (!empty($f['kind']))   { $where[] = 'sub.kind = :k';    $params['k']  = $f['kind']; }
        if (!empty($f['q'])) {
            $where[] = '(sub.skill_name LIKE :q1 OR sub.email LIKE :q2 OR sub.name LIKE :q3 OR sub.reference LIKE :q4)';
            $like = '%' . $f['q'] . '%';
            $params['q1'] = $params['q2'] = $params['q3'] = $params['q4'] = $like;
        }

        $page     = max(1, (int) ($f['page'] ?? 1));
        $perPage  = 20;
        $offset   = ($page - 1) * $perPage;
        $sqlWhere = ' WHERE ' . implode(' AND ', $where);

        $total = (int) Database::scalar('SELECT COUNT(*) ' . self::FROM . $sqlWhere, $params);
        $items = Database::all(
            'SELECT ' . self::SELECT . ' ' . self::FROM . $sqlWhere
            . " ORDER BY FIELD(sub.status,'pending','under_review','approved','rejected','published','archived'),
                 sub.created_at DESC LIMIT " . $perPage . ' OFFSET ' . $offset,
            $params
        );

        return ['items' => $items, 'total' => $total, 'pages' => (int) max(1, ceil($total / $perPage)), 'page' => $page];
    }

    public static function pendingCount(): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*) FROM skill_submissions WHERE status IN ('pending','under_review')"
        );
    }

    public static function statusLabel(string $s): string
    {
        return [
            'pending'      => 'Pendiente',
            'under_review' => 'En revisión',
            'approved'     => 'Aprobada',
            'rejected'     => 'Rechazada',
            'published'    => 'Publicada',
            'archived'     => 'Archivada',
        ][$s] ?? $s;
    }

    public static function statusTone(string $s): string
    {
        return [
            'pending'      => 'amber',
            'under_review' => 'sky',
            'approved'     => 'mint',
            'published'    => 'mint',
            'rejected'     => 'danger',
            'archived'     => '',
        ][$s] ?? '';
    }
}
