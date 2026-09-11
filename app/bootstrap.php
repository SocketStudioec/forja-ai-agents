<?php
declare(strict_types=1);

use App\Core\Config;
use App\Core\Env;
use App\Core\Session;

define('APP_ROOT', dirname(__DIR__));

// ----------------------------------------------------------- Autocarga PSR-4
spl_autoload_register(static function (string $class): void {
    if (strncmp($class, 'App\\', 4) !== 0) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, 4));
    $file     = APP_ROOT . '/app/' . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require APP_ROOT . '/app/helpers.php';

// ------------------------------------------------------------ Configuración
Env::load(APP_ROOT . '/.env');
Config::boot(APP_ROOT);

date_default_timezone_set(Env::get('APP_TIMEZONE', 'America/Guayaquil') ?? 'America/Guayaquil');
mb_internal_encoding('UTF-8');

// -------------------------------------------------------------- Diagnóstico
if (Config::isDebug()) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
}
ini_set('log_errors', '1');

// ------------------------------------------------------ Cabeceras de seguridad
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');
    header_remove('X-Powered-By');

    // Todo el contenido es propio: no hay orígenes de terceros que permitir.
    header(
        "Content-Security-Policy: default-src 'self'; "
        . "img-src 'self' data:; "
        . "style-src 'self' 'unsafe-inline'; "
        . "font-src 'self'; "
        . "script-src 'self'; "
        . "connect-src 'self'; "
        . "form-action 'self'; "
        . "base-uri 'self'; "
        . "frame-ancestors 'self'; "
        . "object-src 'none'"
    );
}

if (PHP_SAPI !== 'cli') {
    Session::start();
}

// ------------------------------------------------------- Errores no atrapados
set_exception_handler(static function (Throwable $e): void {
    error_log('[ai-skills] ' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $e->getMessage() . PHP_EOL);
        exit(1);
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code(500);

    if (Config::isDebug()) {
        header('Content-Type: text/plain; charset=utf-8');
        echo $e->getMessage() . "\n\n" . $e->getTraceAsString();
        exit;
    }

    App\Core\View::render('errors/error', [
        'code'    => 500,
        'title'   => 'Algo se rompió de nuestro lado',
        'message' => 'Registramos el problema. Intenta de nuevo en unos minutos.',
    ], 'Error del servidor');
});
