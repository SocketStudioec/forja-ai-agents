<?php
declare(strict_types=1);

/**
 * Banco de pruebas del correo transaccional.
 *
 *   php scripts/test_correo.php destinatario@dominio.com
 *   php scripts/test_correo.php destinatario@dominio.com submission_sender
 *
 * Envía una muestra de cada plantilla del sistema con datos de prueba
 * claramente marcados. No toca la base de datos salvo el registro en
 * `email_log`, que es justamente lo que permite auditar el resultado.
 */

if (PHP_SAPI !== 'cli') {
    exit("Sólo desde la línea de comandos.\n");
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Config;
use App\Core\Mailer;

$to   = $argv[1] ?? '';
$sola = $argv[2] ?? '';

if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    exit("Uso: php scripts/test_correo.php destinatario@dominio.com [plantilla]\n");
}

$ref   = 'PRUEBA-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
$nota  = '<strong>Esto es un envío de prueba.</strong> El contenido es ficticio y no '
       . 'corresponde a ninguna skill real de la biblioteca.';
$skill = 'Conciliar IVA mensual (ejemplo de prueba)';

$plantillas = [

    // 1 · Aviso al administrador cuando llega un envío
    'submission_admin' => [
        'asunto' => '[Prueba] Nueva skill pendiente de revisión',
        'html'   => Mailer::layout(
            'Nueva skill pendiente de revisión',
            $nota . '<br><br>Alguien envió contenido a la biblioteca. Nada se publica hasta que lo apruebes.',
            [
                'Referencia'     => $ref,
                'Nombre'         => $skill,
                'Tipo'           => 'Habilidad (.json)',
                'Descripción'    => 'Cruza el libro de compras con el anexo transaccional y reporta las diferencias.',
                'Autor'          => 'Remitente de prueba',
                'Correo'         => $to,
                'Categoría'      => 'Contabilidad y tributación',
                'Compatibilidad' => 'OpenClaw, Claude, ChatGPT',
                'Fecha'          => date('d/m/Y H:i'),
            ],
            ['url' => Config::absUrl('/admin/submissions'), 'label' => 'Revisar el envío'],
            'Recibes este aviso porque administras la plataforma.'
        ),
    ],

    // 2 · Confirmación al remitente
    'submission_sender' => [
        'asunto' => '[Prueba] Recibimos tu envío · ' . $ref,
        'html'   => Mailer::layout(
            'Hemos recibido tu envío',
            $nota . '<br><br>Hola, gracias por compartir tu trabajo. Lo revisaremos antes de publicarlo '
            . 'en la biblioteca y te escribiremos a este mismo correo con el resultado.',
            [
                'Identificador' => $ref,
                'Nombre'        => $skill,
                'Estado'        => 'Pendiente de revisión',
                'Fecha'         => date('d/m/Y H:i'),
            ],
            ['url' => Config::absUrl('/skills'), 'label' => 'Explorar la biblioteca'],
            'Guarda el identificador: lo necesitas si quieres consultar o dar de baja tu envío. '
            . 'Usamos tu correo únicamente para informarte del estado de este envío.'
        ),
    ],

    // 3 · Aprobado y publicado
    'submission_approved' => [
        'asunto' => '[Prueba] Tu envío ya está publicado · ' . $ref,
        'html'   => Mailer::layout(
            'Tu envío ya está publicado',
            $nota . '<br><br>Revisamos tu envío y ya está disponible en la biblioteca. Cualquiera puede '
            . 'verlo y descargarlo desde su enlace público.',
            [
                'Identificador' => $ref,
                'Nombre'        => $skill,
                'Enlace'        => Config::absUrl('/skills/conciliar-iva-mensual'),
            ],
            ['url' => Config::absUrl('/skills/conciliar-iva-mensual'), 'label' => 'Ver la publicación'],
            'Gracias por aportar a la biblioteca.'
        ),
    ],

    // 4 · Rechazado con motivo
    'submission_rejected' => [
        'asunto' => '[Prueba] Tu envío requiere cambios · ' . $ref,
        'html'   => Mailer::layout(
            'Tu envío requiere modificaciones',
            $nota . '<br><br>Revisamos lo que nos enviaste y por ahora no podemos publicarlo. Te dejamos '
            . 'el motivo para que puedas corregirlo y volver a enviarlo cuando quieras.',
            [
                'Identificador' => $ref,
                'Nombre'        => $skill,
                'Motivo'        => 'La skill no contiene instrucciones suficientes para su utilización: '
                                 . 'faltan las secciones Workflow, Inputs y Outputs.',
            ],
            ['url' => Config::absUrl('/submit'), 'label' => 'Enviar una versión corregida'],
            'Si crees que se trata de un error, responde a este correo.'
        ),
    ],

    // 5 · Bienvenida al registrarse
    'welcome' => [
        'asunto' => '[Prueba] Tu cuenta en ' . Config::appName(),
        'html'   => Mailer::layout(
            'Cuenta creada',
            $nota . '<br><br>Hola, tu cuenta ya está activa. Desde tu panel puedes crear habilidades en '
            . 'JSON, definir agentes con reglas en Markdown y publicarlos para que cualquiera los descargue.',
            ['Usuario' => 'usuario.prueba', 'Correo' => $to],
            ['url' => Config::absUrl('/dashboard'), 'label' => 'Entrar al panel'],
            'Si no creaste esta cuenta, responde a este correo y la damos de baja.'
        ),
    ],

    // 6 · Recuperación de contraseña
    'password_reset' => [
        'asunto' => '[Prueba] Restablecer tu contraseña',
        'html'   => Mailer::layout(
            'Restablecer contraseña',
            $nota . '<br><br>Recibimos una solicitud para cambiar la contraseña de tu cuenta. El enlace '
            . 'caduca en una hora y sólo puede usarse una vez.',
            ['Cuenta' => $to, 'Solicitado' => date('d/m/Y H:i')],
            ['url' => Config::absUrl('/forgot'), 'label' => 'Crear una contraseña nueva'],
            'Si no fuiste tú, ignora este mensaje: la contraseña actual sigue siendo válida.'
        ),
    ],
];

if ($sola !== '' && !isset($plantillas[$sola])) {
    exit('Plantilla desconocida. Disponibles: ' . implode(', ', array_keys($plantillas)) . PHP_EOL);
}
if ($sola !== '') {
    $plantillas = [$sola => $plantillas[$sola]];
}

echo '› Enviando ' . count($plantillas) . ' correo(s) de prueba a ' . $to . PHP_EOL;

$ok = 0;
foreach ($plantillas as $nombre => $p) {
    $enviado = Mailer::send($to, $p['asunto'], $p['html'], $nombre);
    echo '  ' . ($enviado ? '✓' : '✗') . ' ' . str_pad($nombre, 22) . $p['asunto'] . PHP_EOL;
    if ($enviado) {
        $ok++;
    }
    // El proveedor agradece no recibir seis peticiones en el mismo segundo.
    usleep(400000);
}

echo '  ' . $ok . ' de ' . count($plantillas) . ' enviados.' . PHP_EOL;
echo 'Referencia de esta tanda: ' . $ref . PHP_EOL;
