<?php
// Importa la base de conocimiento desde CSV a MariaDB.
//
//   php private/bin/importar.php --dry-run      solo valida y reporta errores por fila
//   php private/bin/importar.php                valida e importa (todo o nada, en una transacción)
//
// Opciones:
//   --areas=RUTA       (por defecto conocimiento/areas.csv)
//   --preguntas=RUTA   (por defecto conocimiento/preguntas.csv; acepta el CSV exportado del panel)
//
// Áreas: upsert por clave. Preguntas: upsert por slug y reemplazo completo de sus variantes.
// Las preguntas que están en la base pero no en el CSV no se tocan.
// Código de salida: 0 bien, 1 errores de validación o de base de datos.
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}
require __DIR__ . '/../src/bootstrap.php';

use Buscador\Admin\Areas;
use Buscador\Admin\Importador;

$raiz = dirname(__DIR__, 2);
$opc = getopt('', ['dry-run', 'areas:', 'preguntas:', 'help']);
if (isset($opc['help'])) {
    fwrite(STDOUT, "Uso: php private/bin/importar.php [--dry-run] [--areas=RUTA] [--preguntas=RUTA]\n");
    exit(0);
}
$seco = isset($opc['dry-run']);
$rutaAreas = is_string($opc['areas'] ?? null) ? $opc['areas'] : $raiz . '/conocimiento/areas.csv';
$rutaFaqs = is_string($opc['preguntas'] ?? null) ? $opc['preguntas'] : $raiz . '/conocimiento/preguntas.csv';

$enBase = null;
try {
    $enBase = array_keys(Areas::idsPorClave());
} catch (Throwable $e) {
    if (!$seco) {
        fwrite(STDERR, 'Error: no hay conexión con la base de datos: ' . $e->getMessage() . "\n");
        exit(1);
    }
    fwrite(STDOUT, "Aviso: sin conexión a la base; se valida solo contra los CSV.\n");
}

$imp = new Importador();
$ok = $imp->validar($rutaAreas, $rutaFaqs, $enBase);
fwrite(STDOUT, sprintf("Leídas: %d área(s), %d pregunta(s).\n", $imp->totalAreas(), $imp->totalPreguntas()));
foreach ($imp->advertencias as $a) {
    fwrite(STDOUT, '  advertencia: ' . $a . "\n");
}
foreach ($imp->errores as $e) {
    fwrite(STDERR, '  ERROR: ' . $e . "\n");
}
if (!$ok) {
    fwrite(STDERR, count($imp->errores) . " error(es). No se importó nada.\n");
    exit(1);
}
if ($seco) {
    fwrite(STDOUT, "Validación correcta (--dry-run: no se escribió nada).\n");
    exit(0);
}
try {
    $imp->importar();
} catch (Throwable $e) {
    fwrite(STDERR, 'Error al importar (se revirtió todo): ' . $e->getMessage() . "\n");
    exit(1);
}
fwrite(STDOUT, sprintf(
    "Importación terminada: %d área(s), %d pregunta(s), %d variante(s).\n",
    $imp->conteo['areas'], $imp->conteo['preguntas'], $imp->conteo['variantes']
));
exit(0);
