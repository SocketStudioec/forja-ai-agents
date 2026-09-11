<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Renderizador Markdown propio y conservador.
 *
 * Escapa TODO el HTML de entrada antes de aplicar formato, de modo que el
 * contenido enviado por terceros nunca puede inyectar etiquetas ni scripts.
 * Sólo soporta el subconjunto necesario para un SKILL.md.
 */
final class Markdown
{
    /** Separa el front-matter YAML del cuerpo. @return array{0:array<string,mixed>,1:string} */
    public static function splitFrontMatter(string $raw): array
    {
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);
        if (strncmp($raw, "---\n", 4) !== 0) {
            return [[], $raw];
        }
        $end = strpos($raw, "\n---", 3);
        if ($end === false) {
            return [[], $raw];
        }
        $block = substr($raw, 4, $end - 4);
        $body  = ltrim(substr($raw, $end + 4), "\n");

        $meta    = [];
        $listKey = null;
        foreach (explode("\n", $block) as $line) {
            if (preg_match('/^\s*-\s+(.*)$/', $line, $m) && $listKey !== null) {
                $meta[$listKey][] = trim($m[1], " \"'");
                continue;
            }
            if (preg_match('/^([A-Za-z0-9_-]+):\s*(.*)$/', $line, $m)) {
                $key = $m[1];
                $val = trim($m[2], " \"'");
                if ($val === '') {
                    $listKey    = $key;
                    $meta[$key] = [];
                } else {
                    $listKey    = null;
                    $meta[$key] = $val;
                }
            }
        }
        return [$meta, $body];
    }

    /** Extrae las secciones "## Titulo" de un cuerpo markdown. @return array<string,string> */
    public static function sections(string $body): array
    {
        $body     = str_replace(["\r\n", "\r"], "\n", $body);
        $lines    = explode("\n", $body);
        $sections = [];
        $current  = null;
        $buffer   = [];
        $inFence  = false;

        foreach ($lines as $line) {
            if (preg_match('/^\s*```/', $line)) {
                $inFence = !$inFence;
            }
            if (!$inFence && preg_match('/^##\s+(.+?)\s*$/', $line, $m)) {
                if ($current !== null) {
                    $sections[$current] = trim(implode("\n", $buffer));
                }
                $current = trim($m[1]);
                $buffer  = [];
                continue;
            }
            if ($current !== null) {
                $buffer[] = $line;
            }
        }
        if ($current !== null) {
            $sections[$current] = trim(implode("\n", $buffer));
        }
        return $sections;
    }

    public static function toHtml(string $markdown): string
    {
        $md = str_replace(["\r\n", "\r"], "\n", $markdown);
        $md = htmlspecialchars($md, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // Bloques de código con valla, apartados para no reformatearlos.
        $codeBlocks = [];
        $md = preg_replace_callback(
            '/^```([a-zA-Z0-9_+-]*)\n(.*?)^```\s*$/ms',
            static function (array $m) use (&$codeBlocks): string {
                $i = count($codeBlocks);
                $lang = $m[1] !== '' ? ' data-lang="' . $m[1] . '"' : '';
                $codeBlocks[] = '<pre class="md-pre"' . $lang . '><code>' . rtrim($m[2]) . '</code></pre>';
                return "\x02CODE{$i}\x03";
            },
            $md
        ) ?? $md;

        $out      = [];
        $lines    = explode("\n", $md);
        $listType = null;   // 'ul' | 'ol'
        $inQuote  = false;
        $para     = [];
        $tableBuf = [];

        $flushPara = static function () use (&$para, &$out): void {
            if ($para) {
                $out[] = '<p>' . self::inline(implode(' ', $para)) . '</p>';
                $para  = [];
            }
        };
        $closeList = static function () use (&$listType, &$out): void {
            if ($listType !== null) {
                $out[]    = '</' . $listType . '>';
                $listType = null;
            }
        };
        $closeQuote = static function () use (&$inQuote, &$out): void {
            if ($inQuote) {
                $out[]   = '</blockquote>';
                $inQuote = false;
            }
        };
        $flushTable = static function () use (&$tableBuf, &$out): void {
            if (!$tableBuf) {
                return;
            }
            $rows = $tableBuf;
            $tableBuf = [];
            $head = array_shift($rows);
            if ($rows && preg_match('/^[\s|:-]+$/', $rows[0])) {
                array_shift($rows);
            }
            $cells = static function (string $row): array {
                $row = trim($row, "| \t");
                return array_map('trim', explode('|', $row));
            };
            $html = '<div class="md-table-wrap"><table class="md-table"><thead><tr>';
            foreach ($cells($head) as $c) {
                $html .= '<th>' . self::inline($c) . '</th>';
            }
            $html .= '</tr></thead><tbody>';
            foreach ($rows as $r) {
                $html .= '<tr>';
                foreach ($cells($r) as $c) {
                    $html .= '<td>' . self::inline($c) . '</td>';
                }
                $html .= '</tr>';
            }
            $out[] = $html . '</tbody></table></div>';
        };

        foreach ($lines as $line) {
            $trim = trim($line);

            if (preg_match('/^\x02CODE(\d+)\x03$/', $trim, $m)) {
                $flushPara(); $closeList(); $closeQuote(); $flushTable();
                $out[] = $codeBlocks[(int) $m[1]];
                continue;
            }

            if ($trim === '') {
                $flushPara(); $closeList(); $closeQuote(); $flushTable();
                continue;
            }

            // Tabla
            if (strpos($trim, '|') !== false && substr_count($trim, '|') >= 2) {
                $flushPara(); $closeList(); $closeQuote();
                $tableBuf[] = $trim;
                continue;
            }
            $flushTable();

            // Encabezados
            if (preg_match('/^(#{1,6})\s+(.+)$/', $trim, $m)) {
                $flushPara(); $closeList(); $closeQuote();
                // El h1 lo pone siempre la vista con el nombre del recurso, así que
                // el cuerpo arranca en h2: nunca se salta un nivel.
                $lvl = min(6, max(2, strlen($m[1])));
                $txt = self::inline($m[2]);
                $id  = Str::slug(strip_tags($txt), 60);
                $out[] = "<h{$lvl} id=\"s-{$id}\">{$txt}</h{$lvl}>";
                continue;
            }

            // Regla horizontal
            if (preg_match('/^(\*{3,}|-{3,}|_{3,})$/', $trim)) {
                $flushPara(); $closeList(); $closeQuote();
                $out[] = '<hr>';
                continue;
            }

            // Cita
            if (preg_match('/^&gt;\s?(.*)$/', $trim, $m)) {
                $flushPara(); $closeList();
                if (!$inQuote) {
                    $out[]   = '<blockquote>';
                    $inQuote = true;
                }
                $out[] = '<p>' . self::inline($m[1]) . '</p>';
                continue;
            }
            $closeQuote();

            // Lista de tareas / viñetas
            if (preg_match('/^[-*+]\s+(.+)$/', $trim, $m)) {
                $flushPara();
                if ($listType !== 'ul') {
                    $closeList();
                    $out[]    = '<ul>';
                    $listType = 'ul';
                }
                $item = $m[1];
                if (preg_match('/^\[( |x|X)\]\s*(.*)$/', $item, $t)) {
                    $checked = strtolower($t[1]) === 'x' ? ' checked' : '';
                    $out[] = '<li class="md-task"><input type="checkbox" disabled' . $checked . '> ' . self::inline($t[2]) . '</li>';
                } else {
                    $out[] = '<li>' . self::inline($item) . '</li>';
                }
                continue;
            }

            // Lista numerada
            if (preg_match('/^\d+[.)]\s+(.+)$/', $trim, $m)) {
                $flushPara();
                if ($listType !== 'ol') {
                    $closeList();
                    $out[]    = '<ol>';
                    $listType = 'ol';
                }
                $out[] = '<li>' . self::inline($m[1]) . '</li>';
                continue;
            }
            $closeList();

            $para[] = $trim;
        }

        $flushPara(); $closeList(); $closeQuote(); $flushTable();

        return implode("\n", $out);
    }

    /** Formato en línea sobre texto YA escapado. */
    private static function inline(string $text): string
    {
        // Código en línea primero: su contenido no recibe más formato.
        $codes = [];
        $text  = preg_replace_callback('/`([^`]+)`/', static function (array $m) use (&$codes): string {
            $i = count($codes);
            $codes[] = '<code>' . $m[1] . '</code>';
            return "\x02IC{$i}\x03";
        }, $text) ?? $text;

        // Enlaces [texto](url) — sólo http, https y mailto.
        $text = preg_replace_callback(
            '/\[([^\]]+)\]\(([^)\s]+)\)/',
            static function (array $m): string {
                $url = $m[2];
                if (!preg_match('#^(https?://|mailto:|/)#i', $url)) {
                    return $m[1];
                }
                $ext = preg_match('#^https?://#i', $url) ? ' target="_blank" rel="noopener nofollow"' : '';
                return '<a href="' . $url . '"' . $ext . '>' . $m[1] . '</a>';
            },
            $text
        ) ?? $text;

        $text = preg_replace('/\*\*\*([^*]+)\*\*\*/', '<strong><em>$1</em></strong>', $text) ?? $text;
        $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text) ?? $text;
        $text = preg_replace('/(?<![\w*])\*([^*\n]+)\*(?![\w*])/', '<em>$1</em>', $text) ?? $text;
        $text = preg_replace('/(?<![\w_])_([^_\n]+)_(?![\w_])/', '<em>$1</em>', $text) ?? $text;

        return preg_replace_callback('/\x02IC(\d+)\x03/', static fn (array $m) => $codes[(int) $m[1]], $text) ?? $text;
    }
}
