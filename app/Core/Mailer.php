<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Envío de correo transaccional contra la API HTTP configurada en .env.
 * Las credenciales nunca se escriben en código ni en la bitácora.
 */
final class Mailer
{
    public static function send(string $to, string $subject, string $htmlBody, string $template = 'generic'): bool
    {
        $endpoint = Env::get('MAIL_ENDPOINT', '');
        $user     = Env::get('MAIL_USER', '');
        $pass     = Env::get('MAIL_PASS', '');
        $from     = Env::get('MAIL_FROM', 'no-reply@socket-studio.com');

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if ($endpoint === '' || !Env::bool('MAIL_ENABLED', true)) {
            self::record($to, $template, $subject, false, 'envío deshabilitado');
            return false;
        }

        $payload = json_encode([
            'to'      => [$to],
            'from'    => $from,
            'subject' => $subject,
            'html'    => $htmlBody,
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_USERPWD        => $user . ':' . $pass,
            CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
        ]);
        $response = curl_exec($ch);
        $code     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);

        $ok = $code >= 200 && $code < 300;
        self::record($to, $template, $subject, $ok, $ok ? null : ('HTTP ' . $code . ' ' . mb_substr($err ?: (string) $response, 0, 180)));
        return $ok;
    }

    private static function record(string $to, string $template, string $subject, bool $ok, ?string $error): void
    {
        try {
            Database::insert('email_log', [
                'recipient' => mb_substr($to, 0, 190),
                'template'  => mb_substr($template, 0, 60),
                'subject'   => mb_substr($subject, 0, 190),
                'ok'        => $ok ? 1 : 0,
                'error'     => $error ? mb_substr($error, 0, 255) : null,
            ]);
        } catch (\Throwable $e) {
            error_log('[ai-skills] email_log failed: ' . $e->getMessage());
        }
    }

    public static function adminAddress(): string
    {
        return (string) Env::get('ADMIN_EMAIL', 'info@socket-studio.com');
    }

    /** Plantilla HTML común a todos los correos del sistema. */
    public static function layout(string $title, string $intro, array $rows = [], ?array $cta = null, string $footer = ''): string
    {
        $app  = htmlspecialchars(Config::appName(), ENT_QUOTES, 'UTF-8');
        $site = Config::appUrl();

        $rowsHtml = '';
        foreach ($rows as $label => $value) {
            $rowsHtml .= '<tr>'
                . '<td style="padding:10px 14px;border-bottom:1px solid #ECEBE7;font:500 13px/1.4 -apple-system,Segoe UI,Roboto,sans-serif;color:#6B6A65;width:36%;">'
                . htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="padding:10px 14px;border-bottom:1px solid #ECEBE7;font:600 13px/1.5 -apple-system,Segoe UI,Roboto,sans-serif;color:#16161A;">'
                . nl2br(htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8')) . '</td>'
                . '</tr>';
        }

        $ctaHtml = '';
        if ($cta && !empty($cta['url'])) {
            $ctaHtml = '<tr><td style="padding:26px 28px 6px;">'
                . '<a href="' . htmlspecialchars((string) $cta['url'], ENT_QUOTES, 'UTF-8') . '" '
                . 'style="display:inline-block;background:#1B1B1F;color:#FFFFFF;text-decoration:none;'
                . 'font:600 14px/1 -apple-system,Segoe UI,Roboto,sans-serif;padding:14px 22px;border-radius:8px;">'
                . htmlspecialchars((string) ($cta['label'] ?? 'Abrir'), ENT_QUOTES, 'UTF-8') . '</a></td></tr>';
        }

        return '<!doctype html><html lang="es"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title></head>'
            . '<body style="margin:0;padding:24px 12px;background:#F4F3EF;">'
            . '<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:580px;margin:0 auto;background:#FFFFFF;border:1px solid #E5E4DF;border-radius:14px;overflow:hidden;">'
            . '<tr><td style="padding:22px 28px;border-bottom:1px solid #EFEEEA;">'
            . '<span style="font:700 13px/1 ui-monospace,SFMono-Regular,Menlo,monospace;letter-spacing:.14em;text-transform:uppercase;color:#6B6A65;">' . $app . '</span>'
            . '</td></tr>'
            . '<tr><td style="padding:28px 28px 4px;">'
            . '<h1 style="margin:0 0 12px;font:700 22px/1.25 -apple-system,Segoe UI,Roboto,sans-serif;color:#16161A;">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>'
            . '<p style="margin:0;font:400 15px/1.6 -apple-system,Segoe UI,Roboto,sans-serif;color:#4A4A50;">' . $intro . '</p>'
            . '</td></tr>'
            . ($rowsHtml ? '<tr><td style="padding:22px 28px 0;"><table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border:1px solid #ECEBE7;border-radius:10px;border-collapse:separate;overflow:hidden;">' . $rowsHtml . '</table></td></tr>' : '')
            . $ctaHtml
            . '<tr><td style="padding:26px 28px 28px;">'
            . '<p style="margin:0;font:400 12px/1.6 -apple-system,Segoe UI,Roboto,sans-serif;color:#8A8984;">'
            . ($footer !== '' ? $footer . '<br><br>' : '')
            . 'Este mensaje se envió desde <a href="' . $site . '" style="color:#4A4A50;">' . $app . '</a>. '
            . 'Si no esperabas este correo puedes ignorarlo.'
            . '</p></td></tr>'
            . '</table></body></html>';
    }
}
