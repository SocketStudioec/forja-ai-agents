<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Quién puede ver un agente o una habilidad.
 *
 * Es independiente del estado editorial: algo puede estar `published` y aun así
 * ser privado. Para que un recurso aparezca en el catálogo y se pueda descargar
 * sin sesión hacen falta las dos cosas, estado publicado y visibilidad pública.
 */
final class Visibility
{
    public const PUBLIC_   = 'public';
    public const UNLISTED  = 'unlisted';
    public const PRIVATE_  = 'private';

    /** @return array<int,string> */
    public static function all(): array
    {
        return [self::PUBLIC_, self::UNLISTED, self::PRIVATE_];
    }

    public static function isValid(?string $v): bool
    {
        return $v !== null && in_array($v, self::all(), true);
    }

    public static function label(string $v): string
    {
        return [
            self::PUBLIC_  => 'Pública',
            self::UNLISTED => 'Con enlace',
            self::PRIVATE_ => 'Privada',
        ][$v] ?? $v;
    }

    /** Texto de una línea que explica qué implica cada opción. */
    public static function hint(string $v): string
    {
        return [
            self::PUBLIC_  => 'Aparece en el catálogo. Cualquiera la ve y la descarga sin iniciar sesión.',
            self::UNLISTED => 'No aparece en listados, pero quien tenga el enlace la ve y la descarga.',
            self::PRIVATE_ => 'Sólo la ven su autor y los administradores.',
        ][$v] ?? '';
    }

    public static function tone(string $v): string
    {
        return [
            self::PUBLIC_  => 'mint',
            self::UNLISTED => 'sky',
            self::PRIVATE_ => 'danger',
        ][$v] ?? '';
    }

    /** @return array<string,string> valor => etiqueta con su explicación */
    public static function options(): array
    {
        $out = [];
        foreach (self::all() as $v) {
            $out[$v] = self::label($v) . ' — ' . self::hint($v);
        }
        return $out;
    }

    /** Lo contrario de lo que está ahora, para el botón de alternar. */
    public static function toggle(string $current): string
    {
        return $current === self::PUBLIC_ ? self::PRIVATE_ : self::PUBLIC_;
    }
}
