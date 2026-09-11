<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Escritor ZIP en PHP puro.
 *
 * El servidor de producción no tiene la extensión `zip` compilada, así que el
 * paquete se arma a mano con el formato PKZIP 2.0: cabecera local + datos
 * deflactados con gzdeflate + directorio central. Sin dependencias externas.
 */
final class Zip
{
    /** @var array<int,array{name:string,crc:int,csize:int,size:int,offset:int,method:int,mtime:int}> */
    private array $entries = [];
    private string $buffer = '';

    public function add(string $name, string $content, ?int $mtime = null): void
    {
        $name  = ltrim(str_replace('\\', '/', $name), '/');
        $mtime = $mtime ?? time();
        $crc   = crc32($content);
        $size  = strlen($content);

        $deflated = gzdeflate($content, 6);
        if ($deflated === false || strlen($deflated) >= $size) {
            $deflated = $content;   // almacenar sin comprimir
            $method   = 0;
        } else {
            $method = 8;
        }
        $csize  = strlen($deflated);
        $offset = strlen($this->buffer);

        $this->buffer .= "\x50\x4b\x03\x04"
            . pack('v', 20)                    // versión necesaria
            . pack('v', 0x0800)                // flags: nombres en UTF-8
            . pack('v', $method)
            . pack('V', self::dosTime($mtime))
            . pack('V', $crc)
            . pack('V', $csize)
            . pack('V', $size)
            . pack('v', strlen($name))
            . pack('v', 0)
            . $name
            . $deflated;

        $this->entries[] = compact('name', 'crc', 'csize', 'size', 'offset', 'method', 'mtime');
    }

    public function build(): string
    {
        $central = '';
        foreach ($this->entries as $e) {
            $central .= "\x50\x4b\x01\x02"
                . pack('v', 0x031E)            // creado por: UNIX, zip 3.0
                . pack('v', 20)
                . pack('v', 0x0800)
                . pack('v', $e['method'])
                . pack('V', self::dosTime($e['mtime']))
                . pack('V', $e['crc'])
                . pack('V', $e['csize'])
                . pack('V', $e['size'])
                . pack('v', strlen($e['name']))
                . pack('v', 0)                 // extra
                . pack('v', 0)                 // comentario
                . pack('v', 0)                 // disco
                . pack('v', 0)                 // atributos internos
                . pack('V', 0x81A40000)        // externos: archivo regular 0644
                . pack('V', $e['offset'])
                . $e['name'];
        }

        $end = "\x50\x4b\x05\x06"
            . pack('v', 0)
            . pack('v', 0)
            . pack('v', count($this->entries))
            . pack('v', count($this->entries))
            . pack('V', strlen($central))
            . pack('V', strlen($this->buffer))
            . pack('v', 0);

        return $this->buffer . $central . $end;
    }

    private static function dosTime(int $ts): int
    {
        $y = (int) date('Y', $ts);
        if ($y < 1980) {
            return (1 << 21) | (1 << 16);
        }
        return (($y - 1980) << 25)
            | ((int) date('n', $ts) << 21)
            | ((int) date('j', $ts) << 16)
            | ((int) date('G', $ts) << 11)
            | ((int) date('i', $ts) << 5)
            | ((int) ((int) date('s', $ts) / 2));
    }
}
