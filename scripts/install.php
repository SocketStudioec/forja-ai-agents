<?php
declare(strict_types=1);

/**
 * Instalador de línea de comandos.
 *
 *   php scripts/install.php --schema
 *   php scripts/install.php --admin --email=admin@dominio.com --password='...'
 *   php scripts/install.php --seed
 *   php scripts/install.php --all --email=admin@dominio.com --password='...'
 *
 * Nunca se escribe una contraseña en el código: se pasa por argumento o se pide
 * por teclado, y sólo se guarda su hash.
 */

if (PHP_SAPI !== 'cli') {
    exit("Este script sólo se ejecuta desde la línea de comandos.\n");
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\Env;
use App\Core\Str;

$args = [];
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/', $arg, $m)) {
        $args[$m[1]] = $m[2] ?? true;
    }
}

$all = isset($args['all']);
$did = false;

function say(string $msg): void { echo $msg . PHP_EOL; }

// ---------------------------------------------------------------- Esquema
if ($all || isset($args['schema'])) {
    $did = true;
    say('› Creando el esquema…');

    $sql = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
    if ($sql === false) {
        exit("  No se pudo leer database/schema.sql\n");
    }

    // Los comentarios se quitan ANTES de partir por ';': si no, el bloque de
    // comentario que precede a cada CREATE TABLE se lleva por delante la
    // sentencia entera.
    $clean = [];
    foreach (explode("\n", str_replace("\r\n", "\n", $sql)) as $line) {
        $trimmed = ltrim($line);
        if ($trimmed === '' || strncmp($trimmed, '--', 2) === 0) {
            continue;
        }
        $clean[] = $line;
    }

    // Se ejecuta sentencia a sentencia: PDO no admite varias en un exec con
    // prepares nativos activados.
    $pdo        = Database::pdo();
    $statements = array_filter(array_map('trim', explode(';', implode("\n", $clean))));
    $count      = 0;

    foreach ($statements as $stmt) {
        if ($stmt === '') {
            continue;
        }
        try {
            $pdo->exec($stmt);
            $count++;
        } catch (PDOException $e) {
            say('  ! ' . $e->getMessage());
        }
    }
    say("  {$count} sentencias aplicadas.");
}

// ------------------------------------------------------------ Administrador
if ($all || isset($args['admin'])) {
    $did   = true;
    $email = is_string($args['email'] ?? null) ? mb_strtolower(trim($args['email'])) : '';
    $pass  = is_string($args['password'] ?? null) ? $args['password'] : '';
    $name  = is_string($args['name'] ?? null) ? $args['name'] : 'Administrador';

    if ($email === '') {
        echo 'Correo del administrador: ';
        $email = mb_strtolower(trim((string) fgets(STDIN)));
    }
    if ($pass === '') {
        echo 'Contraseña (no se mostrará lo que escribas en la mayoría de terminales): ';
        $pass = trim((string) fgets(STDIN));
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        exit("  El correo no es válido.\n");
    }
    if (mb_strlen($pass) < 10) {
        exit("  La contraseña debe tener al menos 10 caracteres.\n");
    }

    $existing = Database::first('SELECT id FROM users WHERE email = :e', ['e' => $email]);

    if ($existing) {
        Database::run(
            "UPDATE users SET password_hash = :h, role = 'admin', status = 'active' WHERE id = :id",
            ['h' => password_hash($pass, PASSWORD_DEFAULT), 'id' => (int) $existing['id']]
        );
        say('› Administrador actualizado: ' . $email);
    } else {
        $username = Str::slug(explode('@', $email)[0], 40) ?: 'admin';
        $i = 1;
        while (Database::first('SELECT id FROM users WHERE username = :u', ['u' => $username])) {
            $username = 'admin' . (++$i);
        }
        Database::insert('users', [
            'name'          => $name,
            'username'      => $username,
            'email'         => $email,
            'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
            'role'          => 'admin',
            'status'        => 'active',
        ]);
        say('› Administrador creado: ' . $email . ' (usuario: ' . $username . ')');
    }
}

// -------------------------------------------------------- Contenido inicial
if ($all || isset($args['seed'])) {
    $did = true;
    say('› Cargando categorías y contenido de ejemplo…');
    require __DIR__ . '/seed_data.php';
    seedContent();
}

if (!$did) {
    say('Uso: php scripts/install.php [--schema] [--admin --email=… --password=…] [--seed] [--all]');
}

say('Listo.');
