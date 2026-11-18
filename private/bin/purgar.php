<?php
// Purga de datos para cron: borra de bf_consulta lo más viejo que privacidad.dias_retencion
// y limpia los contadores vencidos de bf_limite. Sin salida salvo errores; código != 0 si falla.
//
// Línea de cron (CWP > Cron Jobs), todos los días a las 03:15:
//   15 3 * * * /usr/local/bin/php /home/USUARIO/private-buscador/bin/purgar.php
// (ajusta la ruta de PHP según "which php" o la versión elegida en CWP, p. ej. /opt/alt/php74/usr/bin/php)
//
// Opción --verbose: muestra cuántas filas se borraron (útil para probar a mano).
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}
require __DIR__ . '/../src/bootstrap.php';

use Buscador\Support\Config;
use Buscador\Support\Db;

$verbose = in_array('--verbose', $argv, true);
try {
    $dias = (int) Config::get('privacidad.dias_retencion', 90);
    if ($dias < 1) {
        throw new RuntimeException('privacidad.dias_retencion debe ser un entero mayor que 0.');
    }
    $limite = (new DateTimeImmutable('now'))->modify('-' . $dias . ' days')->format('Y-m-d H:i:s');

    // En lotes para no bloquear la tabla mientras el buscador sigue registrando.
    $consultas = 0;
    do {
        $n = Db::ejecutar('DELETE FROM bf_consulta WHERE creado_en < ? ORDER BY id LIMIT 5000', [$limite]);
        $consultas += $n;
    } while ($n === 5000);

    $limites = \Buscador\Support\RateLimiter::limpiar();
    if ($verbose) {
        fwrite(STDOUT, "bf_consulta: $consultas fila(s) anteriores a $limite borradas; bf_limite: $limites contador(es) vencidos.\n");
    }
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, '[purgar] ' . date('c') . ' ' . $e->getMessage() . "\n");
    exit(1);
}
