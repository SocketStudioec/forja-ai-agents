<?php
declare(strict_types=1);

/**
 * Aplica un archivo de migración .sql.
 *
 *   php scripts/migrate.php database/migrations/003_paid_tier.sql
 *
 * Es tolerante a repeticiones: si una columna o un índice ya existe, lo dice y
 * sigue. Cualquier otro error se reporta y detiene la migración, para no dejar
 * el esquema a medias sin que nadie se entere.
 */

if (PHP_SAPI !== 'cli') {
    exit("Sólo desde la línea de comandos.\n");
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

$archivo = $argv[1] ?? '';
if ($archivo === '') {
    exit("Uso: php scripts/migrate.php <ruta-al-archivo.sql>\n");
}

$ruta = is_file($archivo) ? $archivo : dirname(__DIR__) . '/' . ltrim($archivo, '/');
if (!is_readable($ruta)) {
    exit('No se puede leer: ' . $archivo . PHP_EOL);
}

$sql = (string) file_get_contents($ruta);

// Se quitan los comentarios antes de partir por ';', o el bloque de cabecera
// se lleva por delante la primera sentencia.
$limpias = [];
foreach (explode("\n", str_replace("\r\n", "\n", $sql)) as $linea) {
    $t = ltrim($linea);
    if ($t === '' || strncmp($t, '--', 2) === 0) {
        continue;
    }
    $limpias[] = $linea;
}

$sentencias = array_filter(array_map('trim', explode(';', implode("\n", $limpias))));

// Errores que significan «esto ya estaba aplicado», no «esto falló».
$yaAplicado = ['duplicate column', 'duplicate key name', 'already exists', 'duplicate entry'];

$pdo       = Database::pdo();
$aplicadas = 0;
$omitidas  = 0;

echo '› ' . basename($ruta) . ': ' . count($sentencias) . ' sentencia(s)' . PHP_EOL;

foreach ($sentencias as $s) {
    if ($s === '') {
        continue;
    }
    try {
        $pdo->exec($s);
        $aplicadas++;
        echo '  ✓ ' . mb_substr(preg_replace('/\s+/', ' ', $s) ?? '', 0, 78) . PHP_EOL;
    } catch (PDOException $e) {
        $msg = mb_strtolower($e->getMessage());
        $conocido = false;
        foreach ($yaAplicado as $pista) {
            if (strpos($msg, $pista) !== false) {
                $conocido = true;
                break;
            }
        }
        if ($conocido) {
            $omitidas++;
            echo '  · ya estaba: ' . mb_substr(preg_replace('/\s+/', ' ', $s) ?? '', 0, 60) . PHP_EOL;
            continue;
        }
        echo '  ✗ ' . $e->getMessage() . PHP_EOL;
        exit(1);
    }
}

echo '  ' . $aplicadas . ' aplicada(s), ' . $omitidas . ' ya estaban.' . PHP_EOL;
echo 'Listo.' . PHP_EOL;
