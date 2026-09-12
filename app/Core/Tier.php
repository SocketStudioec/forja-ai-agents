<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Plantillas gratuitas y de pago.
 *
 * Una plantilla de pago se ve en el catálogo con su nombre, sus categorías y
 * una descripción de lo que hace, pero su contenido no se previsualiza ni se
 * descarga. Sólo un administrador puede marcar algo como de pago.
 *
 * La comprobación vive en el servidor, en la descarga y en las vistas. Ocultar
 * el botón no protege nada: el enlace de descarga es adivinable.
 */
final class Tier
{
    public const FREE = 'free';
    public const PAID = 'paid';

    /** @return array<int,string> */
    public static function all(): array
    {
        return [self::FREE, self::PAID];
    }

    public static function isValid(?string $t): bool
    {
        return $t !== null && in_array($t, self::all(), true);
    }

    /** @param array<string,mixed> $row */
    public static function isPaid(array $row): bool
    {
        return ($row['tier'] ?? self::FREE) === self::PAID;
    }

    public static function label(string $t): string
    {
        return $t === self::PAID ? 'De pago' : 'Gratuita';
    }

    public static function hint(string $t): string
    {
        return $t === self::PAID
            ? 'Se ve en el catálogo con su descripción, pero no se descarga ni se previsualiza su contenido.'
            : 'Cualquiera puede verla y descargarla sin registrarse.';
    }

    /**
     * Sólo un administrador puede marcar algo como de pago. Para cualquier otro
     * usuario se conserva lo que ya tuviera el recurso, de modo que ni puede
     * crear una de pago ni liberar una existente.
     */
    public static function resolve(?string $pedido, string $actual = self::FREE): string
    {
        if (!Auth::isAdmin()) {
            return self::isValid($actual) ? $actual : self::FREE;
        }
        return self::isValid($pedido) ? $pedido : $actual;
    }

    /** A dónde se manda a quien quiere una plantilla de pago. */
    public static function contactUrl(array $row): string
    {
        $propio = trim((string) ($row['contact_url'] ?? ''));
        if ($propio !== '' && preg_match('#^(https?://|mailto:)#i', $propio)) {
            return $propio;
        }
        return 'mailto:' . Mailer::adminAddress()
            . '?subject=' . rawurlencode('Consulta sobre ' . ($row['name'] ?? 'una plantilla de pago'));
    }
}
