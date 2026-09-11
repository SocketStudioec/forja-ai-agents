<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Limitador por IP con ventana deslizante en base de datos.
 * Protege login, registro, recuperación de contraseña y envío de skills.
 */
final class RateLimit
{
    public static function tooMany(string $bucket, int $max, int $windowSeconds): bool
    {
        // La ventana es un entero de la propia aplicación, nunca entrada del
        // usuario; MySQL no acepta marcadores dentro de INTERVAL.
        $sql = sprintf(
            'SELECT COUNT(*) FROM rate_limits
             WHERE bucket = :b AND ip_hash = :ip AND created_at > (NOW() - INTERVAL %d SECOND)',
            max(1, $windowSeconds)
        );
        $count = (int) Database::scalar($sql, ['b' => $bucket, 'ip' => Str::ipHash()]);
        return $count >= $max;
    }

    public static function hit(string $bucket): void
    {
        Database::insert('rate_limits', ['bucket' => $bucket, 'ip_hash' => Str::ipHash()]);

        // Limpieza oportunista: 1 de cada 25 peticiones purga lo viejo.
        if (random_int(1, 25) === 1) {
            Database::run('DELETE FROM rate_limits WHERE created_at < (NOW() - INTERVAL 1 DAY)');
        }
    }

    public static function clear(string $bucket): void
    {
        Database::run(
            'DELETE FROM rate_limits WHERE bucket = :b AND ip_hash = :ip',
            ['b' => $bucket, 'ip' => Str::ipHash()]
        );
    }
}
