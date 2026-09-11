<?php
declare(strict_types=1);

namespace App\Core;

final class Str
{
    public static function slug(string $text, int $max = 120): string
    {
        $text = self::ascii($text);
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        $text = trim($text, '-');
        if ($text === '') {
            $text = 'item-' . substr(bin2hex(random_bytes(4)), 0, 6);
        }
        return substr($text, 0, $max);
    }

    public static function ascii(string $text): string
    {
        $map = [
            'á'=>'a','à'=>'a','ä'=>'a','â'=>'a','ã'=>'a','å'=>'a',
            'é'=>'e','è'=>'e','ë'=>'e','ê'=>'e',
            'í'=>'i','ì'=>'i','ï'=>'i','î'=>'i',
            'ó'=>'o','ò'=>'o','ö'=>'o','ô'=>'o','õ'=>'o',
            'ú'=>'u','ù'=>'u','ü'=>'u','û'=>'u',
            'ñ'=>'n','ç'=>'c','ý'=>'y','ÿ'=>'y',
            'Á'=>'A','À'=>'A','Ä'=>'A','Â'=>'A','Ã'=>'A','Å'=>'A',
            'É'=>'E','È'=>'E','Ë'=>'E','Ê'=>'E',
            'Í'=>'I','Ì'=>'I','Ï'=>'I','Î'=>'I',
            'Ó'=>'O','Ò'=>'O','Ö'=>'O','Ô'=>'O','Õ'=>'O',
            'Ú'=>'U','Ù'=>'U','Ü'=>'U','Û'=>'U',
            'Ñ'=>'N','Ç'=>'C','ª'=>'a','º'=>'o','·'=>'-','—'=>'-','–'=>'-',
        ];
        return strtr($text, $map);
    }

    public static function excerpt(string $text, int $chars = 160): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($text)) ?? '');
        if (mb_strlen($text) <= $chars) {
            return $text;
        }
        $cut = mb_substr($text, 0, $chars);
        $sp  = mb_strrpos($cut, ' ');
        if ($sp !== false && $sp > (int) ($chars * 0.6)) {
            $cut = mb_substr($cut, 0, $sp);
        }
        return $cut . '…';
    }

    /** @return array<int,string> */
    public static function listFromCsv(?string $csv): array
    {
        if ($csv === null || trim($csv) === '') {
            return [];
        }
        $parts = array_map('trim', explode(',', $csv));
        return array_values(array_unique(array_filter($parts, static fn ($p) => $p !== '')));
    }

    public static function csvFromList(array $list, int $max = 12): string
    {
        $clean = [];
        foreach ($list as $item) {
            $item = trim((string) $item);
            if ($item !== '' && !in_array($item, $clean, true)) {
                $clean[] = mb_substr($item, 0, 40);
            }
            if (count($clean) >= $max) {
                break;
            }
        }
        return implode(', ', $clean);
    }

    /** Valida y normaliza un semver MAJOR.MINOR.PATCH. */
    public static function version(?string $v, string $fallback = '1.0.0'): string
    {
        $v = trim((string) $v);
        if (preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,4}$/', $v)) {
            return $v;
        }
        if (preg_match('/^\d{1,3}\.\d{1,3}$/', $v)) {
            return $v . '.0';
        }
        return $fallback;
    }

    /** Incrementa la parte indicada de un semver. */
    public static function bumpVersion(string $current, string $part = 'patch'): string
    {
        [$maj, $min, $pat] = array_map('intval', explode('.', self::version($current)));
        if ($part === 'major') { return ($maj + 1) . '.0.0'; }
        if ($part === 'minor') { return $maj . '.' . ($min + 1) . '.0'; }
        return $maj . '.' . $min . '.' . ($pat + 1);
    }

    public static function reference(string $prefix = 'SK'): string
    {
        return $prefix . '-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }

    public static function ipHash(): string
    {
        $ip   = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ip   = trim(explode(',', (string) $ip)[0]);
        $salt = Env::get('APP_KEY', 'ai-skills');
        return hash('sha256', $salt . '|' . $ip);
    }

    public static function timeAgo(?string $datetime): string
    {
        if (!$datetime) {
            return '—';
        }
        $ts   = strtotime($datetime);
        $diff = time() - $ts;
        if ($diff < 60)      { return 'hace un momento'; }
        if ($diff < 3600)    { return 'hace ' . (int) ($diff / 60) . ' min'; }
        if ($diff < 86400)   { return 'hace ' . (int) ($diff / 3600) . ' h'; }
        if ($diff < 2592000) { return 'hace ' . (int) ($diff / 86400) . ' d'; }
        return date('d/m/Y', $ts);
    }

    public static function humanBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return round($bytes / 1048576, 2) . ' MB';
    }

    public static function compactNumber(int $n): string
    {
        if ($n < 1000) {
            return (string) $n;
        }
        if ($n < 1000000) {
            return rtrim(rtrim(number_format($n / 1000, 1, '.', ''), '0'), '.') . 'k';
        }
        return rtrim(rtrim(number_format($n / 1000000, 1, '.', ''), '0'), '.') . 'M';
    }
}
