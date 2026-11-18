<?php
// Evaluación repetible del buscador (solo línea de comandos).
//
//   php private/bin/evaluar.php tests/preguntas/desarrollo.csv
//   php private/bin/evaluar.php tests/preguntas/aceptacion.csv --nombre=aceptacion --bd
//   php private/bin/evaluar.php --interactivo                 (el revisor escribe las preguntas)
//   php private/bin/evaluar.php preguntas.csv --interactivo   (recorre el CSV y pide el veredicto de cada una)
//
// CSV de entrada (UTF-8, con o sin BOM):  consulta,esperado[,grupo]
//   esperado = slug de la pregunta, "sin_respuesta" o "no_ofertada"; varios aceptables separados por "|".
//   grupo    = opcional; filas CONSECUTIVAS con el mismo grupo comparten el contexto (conversación).
//
// Opciones:
//   --csv               usa conocimiento/*.csv como base de conocimiento
//   --bd                usa la base de datos de la configuración (falla si no hay conexión)
//                       (por defecto: base de datos si existe private/config.php y conecta; si no, CSV)
//   --conocimiento=DIR  carpeta con preguntas.csv y areas.csv (por defecto conocimiento/)
//   --config=RUTA       archivo de configuración (por defecto BF_CONFIG, private/config.php o tests/config.pruebas.php)
//   --nombre=TEXTO      nombre para el informe (por defecto, el del archivo CSV)
//   --salida=DIR        carpeta de informes (por defecto tests/resultados/)
//   --sin-informe       no escribe archivos .md/.json
//   --minimo=N          código de salida 1 si los aciertos directos quedan por debajo de N %
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$raiz = dirname(__DIR__, 2);      // raíz del repositorio (en el servidor, la carpeta que contiene a private/)
$privada = dirname(__DIR__);       // private/ (en el servidor puede llamarse private-buscador/)
$op = ['csv' => false, 'bd' => false, 'interactivo' => false, 'sin-informe' => false, 'conocimiento' => $raiz . '/conocimiento',
       'config' => '', 'nombre' => '', 'minimo' => '',
       'salida' => is_dir($raiz . '/tests') ? $raiz . '/tests/resultados' : $privada . '/logs'];
$archivo = '';
foreach (array_slice($argv, 1) as $a) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/', $a, $m)) {
        if (!array_key_exists($m[1], $op)) {
            fwrite(STDERR, "Opción desconocida: --{$m[1]}\n");
            exit(1);
        }
        $op[$m[1]] = isset($m[2]) ? $m[2] : true;
    } elseif ($a === '-h') {
        $op['ayuda'] = true;
    } else {
        $archivo = $a;
    }
}
if (!empty($op['ayuda'])) {
    echo "Uso: php private/bin/evaluar.php [preguntas.csv] [--csv|--bd] [--interactivo] [--nombre=..] [--salida=..] [--minimo=85]\n";
    exit(0);
}

// --------------------------------------------------------------- configuración
$configPropia = is_file($privada . '/config.php');
if ($op['config'] !== '') {
    putenv('BF_CONFIG=' . $op['config']);
} elseif (getenv('BF_CONFIG')) {
    // se respeta la variable de entorno
} elseif (!$configPropia) {
    putenv('BF_CONFIG=' . $raiz . '/tests/config.pruebas.php');
}
require __DIR__ . '/../src/bootstrap.php';
ini_set('display_errors', '1');

use Buscador\Search\Buscador;
use Buscador\Search\Csv;
use Buscador\Search\Indice;
use Buscador\Search\Repositorio;
use Buscador\Search\Texto;
use Buscador\Support\Config;

$rutaConfig = getenv('BF_CONFIG') ?: $privada . '/config.php';

// --------------------------------------------------------------- base de conocimiento
$fuente = 'csv';
$avisoFuente = '';
if ($op['bd'] || (!$op['csv'] && ($configPropia || $op['config'] !== '' ))) {
    try {
        $n = count(Repositorio::faqsActivas());
        if ($n === 0) {
            throw new RuntimeException('la tabla bf_faq no tiene preguntas activas');
        }
        $fuente = 'bd';
    } catch (Throwable $e) {
        if ($op['bd']) {
            fwrite(STDERR, 'No se pudo usar la base de datos: ' . $e->getMessage() . "\n");
            exit(1);
        }
        $avisoFuente = 'Sin conexión a la base de datos (' . $e->getMessage() . '); se usa conocimiento/*.csv.';
    }
}
$dirCon = rtrim((string) $op['conocimiento'], '/\\');

/** @return array<int,array<string,mixed>> */
$cargar = static function () use ($fuente, $dirCon): array {
    return $fuente === 'bd'
        ? Repositorio::faqsActivas()
        : Csv::faqsEnMemoria($dirCon . '/preguntas.csv', $dirCon . '/areas.csv');
};

$faqs = $cargar();
$porId = [];
$porSlug = [];
foreach ($faqs as $f) {
    $porId[(int) $f['id']] = $f;
    $porSlug[(string) $f['slug']] = $f;
}
$descFuente = $fuente === 'bd'
    ? 'base de datos ' . Config::get('db.nombre') . '@' . Config::get('db.host')
    : 'CSV ' . relativa($dirCon . '/preguntas.csv', $raiz);

/** Ejecuta una búsqueda como lo hace el endpoint: carga la base, construye el índice y busca. */
$buscar = static function (string $consulta, array $contexto) use ($cargar): array {
    $t = microtime(true);
    $motor = new Buscador(new Indice($cargar()));
    $r = $motor->buscar($consulta, $contexto);
    $r['ms_total'] = (microtime(true) - $t) * 1000;
    return $r;
};

// --------------------------------------------------------------- utilidades
function relativa(string $ruta, string $raiz): string
{
    $r = str_replace('\\', '/', $ruta);
    $b = str_replace('\\', '/', $raiz) . '/';
    return strpos($r, $b) === 0 ? substr($r, strlen($b)) : $r;
}

/** @param float[] $v */
function percentil(array $v, float $p): float
{
    if (!$v) {
        return 0.0;
    }
    sort($v);
    $k = (int) max(0, ceil($p / 100 * count($v)) - 1);
    return $v[$k];
}

function pct(int $a, int $n): string
{
    return $n > 0 ? number_format(100 * $a / $n, 1) . ' %' : '—';
}

function celda($t): string
{
    return str_replace(['|', "\n", "\r"], ['\\|', ' ', ''], (string) $t);
}

function utf8(string $s): string
{
    $s = trim($s);
    return mb_check_encoding($s, 'UTF-8') ? $s : mb_convert_encoding($s, 'UTF-8', 'Windows-1252');
}

/** Slug (o etiqueta) de lo que devolvió el motor. */
function obtenido(array $r, array $porId): string
{
    $slug = static function ($id) use ($porId) {
        return isset($porId[(int) $id]) ? (string) $porId[(int) $id]['slug'] : '#' . $id;
    };
    switch ($r['resultado']) {
        case 'respondida':
            return $slug($r['faq_id']);
        case 'sugerencias':
            return 'sugerencias: ' . implode(', ', array_map(static function ($s) use ($slug) {
                return $slug($s['id']);
            }, $r['sugerencias']));
    }
    return (string) $r['resultado'];
}

/**
 * Califica un resultado contra lo esperado.
 * @param string[] $esperados
 * @return string 'directo' | 'sugerencia' | 'fallo'
 */
function calificarResultado(array $r, array $esperados, array $porId): string
{
    $slugDe = static function ($id) use ($porId) {
        return isset($porId[(int) $id]) ? (string) $porId[(int) $id]['slug'] : null;
    };
    switch ($r['resultado']) {
        case 'respondida':
            return in_array($slugDe($r['faq_id']), $esperados, true) ? 'directo' : 'fallo';
        case 'sugerencias':
            $primera = $r['sugerencias'][0]['id'] ?? null;
            return $primera !== null && in_array($slugDe($primera), $esperados, true) ? 'sugerencia' : 'fallo';
        case 'sin_respuesta':
        case 'no_ofertada':
            return in_array($r['resultado'], $esperados, true) ? 'directo' : 'fallo';
    }
    return 'fallo';
}

function mostrarResultado(array $r, array $porId): void
{
    printf("  → %s · confianza %.3f · %.0f ms\n", strtoupper($r['resultado']), $r['confianza'], $r['ms_total']);
    if (!empty($r['respuesta'])) {
        $slug = isset($r['faq_id'], $porId[(int) $r['faq_id']]) ? ' [' . $porId[(int) $r['faq_id']]['slug'] . ']' : '';
        echo '  ' . $r['respuesta']['pregunta'] . $slug . "\n";
        $lineas = explode("\n", (string) $r['respuesta']['texto']);
        foreach (array_slice($lineas, 0, 8) as $l) {
            echo '    ' . $l . "\n";
        }
        if (count($lineas) > 8) {
            echo "    …\n";
        }
        foreach ($r['respuesta']['enlaces'] ?? [] as $e) {
            echo '    ↗ ' . $e['titulo'] . ': ' . $e['url'] . "\n";
        }
    }
    foreach ($r['sugerencias'] as $i => $s) {
        $slug = isset($porId[(int) $s['id']]) ? ' [' . $porId[(int) $s['id']]['slug'] . ']' : '';
        printf("  %s %d. %s%s (%.3f)\n", $r['resultado'] === 'sugerencias' ? '¿Quisiste decir?' : 'Relacionada', $i + 1, $s['pregunta'], $slug, $s['confianza']);
    }
    if (!empty($r['canalizar'])) {
        echo '  Canalizar a: ' . $r['canalizar']['nombre'] . ($r['canalizar']['url'] ? ' (' . $r['canalizar']['url'] . ')' : '') . "\n";
    }
    if (!empty($r['contexto']['carrera'])) {
        echo '  Carrera en contexto: ' . $r['contexto']['carrera'] . (!empty($r['diagnostico']['uso_contexto']) ? ' (heredada del mensaje anterior)' : '') . "\n";
    }
}

/** @param array<string,mixed> $datos */
function escribirInforme(string $dir, string $base, string $md, array $datos): array
{
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('No se pudo crear ' . $dir);
    }
    $rutaMd = $dir . '/' . $base . '.md';
    $rutaJson = $dir . '/' . $base . '.json';
    file_put_contents($rutaMd, $md);
    file_put_contents($rutaJson, json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
    return [$rutaMd, $rutaJson];
}

$umbrales = [
    'umbral_respuesta'   => (float) Config::get('busqueda.umbral_respuesta', 0.62),
    'umbral_sugerencias' => (float) Config::get('busqueda.umbral_sugerencias', 0.30),
    'max_sugerencias'    => (int) Config::get('busqueda.max_sugerencias', 3),
];
$entorno = [
    'php'            => PHP_VERSION,
    'so'             => PHP_OS_FAMILY . ' ' . php_uname('r'),
    'fecha'          => date('Y-m-d H:i:s T'),
    'fuente'         => $fuente,
    'fuente_detalle' => $descFuente,
    'config'         => relativa((string) $rutaConfig, $raiz),
    'preguntas_base' => count($faqs),
    'umbrales'       => $umbrales,
];

// =============================================================== modo interactivo
if ($op['interactivo']) {
    $lista = null;
    if ($archivo !== '') {
        $lista = array_values(array_filter(array_map(static function ($f) {
            return trim((string) ($f['consulta'] ?? ''));
        }, Csv::leer($archivo)), 'strlen'));
    }
    echo "Evaluación interactiva · PHP " . PHP_VERSION . " · base: $descFuente (" . count($faqs) . " preguntas)\n";
    if ($avisoFuente !== '') {
        echo "Aviso: $avisoFuente\n";
    }
    echo "Escribe una pregunta y pulsa Enter. Comandos: :nuevo (reinicia el contexto de conversación), :fin (termina).\n";
    echo "Después de cada respuesta indica: s = correcta · g = correcta en la primera sugerencia · n = incorrecta · x = no contar.\n";
    $registro = [];
    $contexto = [];
    $i = 0;
    while (true) {
        if ($lista !== null) {
            if ($i >= count($lista)) {
                break;
            }
            $q = $lista[$i++];
            echo "\nPregunta " . $i . '/' . count($lista) . '> ' . $q . "\n";
        } else {
            echo "\nPregunta> ";
            $linea = fgets(STDIN);
            if ($linea === false) {
                break;
            }
            $q = utf8($linea);
            if ($q === '') {
                continue;
            }
            if ($q === ':fin' || $q === 'salir') {
                break;
            }
            if ($q === ':nuevo') {
                $contexto = [];
                echo "  (contexto reiniciado)\n";
                continue;
            }
        }
        $r = $buscar($q, $contexto);
        $contexto = $r['contexto'];
        mostrarResultado($r, $porId);
        $veredicto = null;
        while ($veredicto === null) {
            echo '¿Fue correcto? [s/g/n/x]: ';
            $v = fgets(STDIN);
            if ($v === false) {
                $veredicto = 'x';
                break;
            }
            $v = strtolower(trim($v));
            $mapa = ['s' => 'directo', 'si' => 'directo', 'sí' => 'directo', 'g' => 'sugerencia', 'n' => 'fallo', 'no' => 'fallo', 'x' => 'omitida'];
            $veredicto = $mapa[$v] ?? null;
        }
        $registro[] = ['consulta' => $q, 'resultado' => $r['resultado'], 'obtenido' => obtenido($r, $porId),
                       'confianza' => $r['confianza'], 'ms' => round($r['ms_total'], 1), 'veredicto' => $veredicto];
        $cont = array_filter($registro, static function ($x) {
            return $x['veredicto'] !== 'omitida';
        });
        $d = count(array_filter($cont, static function ($x) {
            return $x['veredicto'] === 'directo';
        }));
        echo '  Llevamos: ' . $d . '/' . count($cont) . ' correctas (' . pct($d, count($cont)) . ")\n";
    }
    $cont = array_values(array_filter($registro, static function ($x) {
        return $x['veredicto'] !== 'omitida';
    }));
    $n = count($cont);
    $dir = count(array_filter($cont, static function ($x) {
        return $x['veredicto'] === 'directo';
    }));
    $sug = count(array_filter($cont, static function ($x) {
        return $x['veredicto'] === 'sugerencia';
    }));
    $tiempos = array_column($registro, 'ms');
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "Preguntas evaluadas: $n (omitidas: " . (count($registro) - $n) . ")\n";
    echo 'Correctas directas:          ' . $dir . ' (' . pct($dir, $n) . ")\n";
    echo 'Incluyendo sugerencias:      ' . ($dir + $sug) . ' (' . pct($dir + $sug, $n) . ")\n";
    printf("Tiempo por consulta: p50 %.1f ms · p95 %.1f ms · máx %.1f ms\n", percentil($tiempos, 50), percentil($tiempos, 95), $tiempos ? max($tiempos) : 0);
    if ($registro && !$op['sin-informe']) {
        $nombre = $op['nombre'] !== '' ? $op['nombre'] : 'interactiva';
        $base = 'evaluacion-' . Texto::slug($nombre) . '-' . date('Y-m-d_His');
        $md = "# Evaluación interactiva: $nombre\n\n"
            . "- Fecha: {$entorno['fecha']}\n- PHP: {$entorno['php']} ({$entorno['so']})\n- Base de conocimiento: $descFuente ({$entorno['preguntas_base']} preguntas)\n\n"
            . "| Medida | Valor |\n|---|---|\n| Preguntas evaluadas | $n |\n| Correctas directas | $dir (" . pct($dir, $n) . ") |\n"
            . '| Incluyendo sugerencias | ' . ($dir + $sug) . ' (' . pct($dir + $sug, $n) . ") |\n"
            . sprintf("| Tiempo p50 / p95 / máx | %.1f / %.1f / %.1f ms |\n\n", percentil($tiempos, 50), percentil($tiempos, 95), $tiempos ? max($tiempos) : 0)
            . "| # | Consulta | Resultado | Obtenido | Confianza | Veredicto |\n|---|---|---|---|---|---|\n";
        foreach ($registro as $k => $x) {
            $md .= '| ' . ($k + 1) . ' | ' . celda($x['consulta']) . ' | ' . $x['resultado'] . ' | ' . celda($x['obtenido']) . ' | ' . $x['confianza'] . ' | ' . $x['veredicto'] . " |\n";
        }
        list($a, $b) = escribirInforme((string) $op['salida'], $base, $md, ['entorno' => $entorno, 'resumen' => ['evaluadas' => $n, 'directas' => $dir, 'sugerencia' => $sug], 'consultas' => $registro]);
        echo "Informe: " . relativa($a, $raiz) . "\n         " . relativa($b, $raiz) . "\n";
    }
    exit(0);
}

// =============================================================== modo por lote
if ($archivo === '') {
    $archivo = $raiz . '/tests/preguntas/desarrollo.csv';
}
if (!is_file($archivo)) {
    fwrite(STDERR, "No existe el archivo de preguntas: $archivo\n");
    exit(1);
}
$filas = Csv::leer($archivo);
if (!$filas || !array_key_exists('consulta', $filas[0]) || !array_key_exists('esperado', $filas[0])) {
    fwrite(STDERR, "El CSV debe tener las columnas: consulta,esperado[,grupo]\n");
    exit(1);
}
$nombre = $op['nombre'] !== '' ? (string) $op['nombre'] : pathinfo($archivo, PATHINFO_FILENAME);

echo "Evaluando " . relativa(realpath($archivo) ?: $archivo, $raiz) . " · PHP " . PHP_VERSION . " · base: $descFuente (" . count($faqs) . " preguntas)\n";
if ($avisoFuente !== '') {
    echo "Aviso: $avisoFuente\n";
}

$detalle = [];
$avisos = [];
$contexto = [];
$grupoAnterior = null;
foreach ($filas as $i => $f) {
    $consulta = trim((string) $f['consulta']);
    if ($consulta === '') {
        continue;
    }
    $esperados = array_values(array_filter(array_map(static function ($e) {
        return strtolower(trim($e));
    }, explode('|', (string) $f['esperado'])), 'strlen'));
    foreach ($esperados as $e) {
        if ($e !== 'sin_respuesta' && $e !== 'no_ofertada' && !isset($porSlug[$e])) {
            $avisos[] = 'Fila ' . ($i + 2) . ": el slug esperado '$e' no existe en la base de conocimiento.";
        }
    }
    $grupo = trim((string) ($f['grupo'] ?? ''));
    if ($grupo === '' || $grupo !== $grupoAnterior) {
        $contexto = [];
    }
    $r = $buscar($consulta, $contexto);
    $contexto = $r['contexto'];
    $grupoAnterior = $grupo;

    $calif = calificarResultado($r, $esperados, $porId);
    $primero = $esperados[0] ?? '';
    $categoria = isset($porSlug[$primero]) ? (string) $porSlug[$primero]['categoria'] : ($primero !== '' ? $primero : '(sin esperado)');
    if ($primero !== '' && !isset($porSlug[$primero]) && !in_array($primero, ['sin_respuesta', 'no_ofertada'], true)) {
        $categoria = '(slug desconocido)';
    }
    $detalle[] = [
        'fila'       => $i + 2,
        'consulta'   => $consulta,
        'grupo'      => $grupo,
        'esperado'   => implode('|', $esperados),
        'categoria'  => $categoria,
        'resultado'  => $r['resultado'],
        'obtenido'   => obtenido($r, $porId),
        'confianza'  => $r['confianza'],
        'calificacion' => $calif,
        'ms'         => round($r['ms_total'], 2),
        'terminos'   => $r['diagnostico']['terminos'] ?? [],
        'carrera'    => $r['contexto']['carrera'] ?? null,
        'uso_contexto' => (bool) ($r['diagnostico']['uso_contexto'] ?? false),
    ];
    echo $calif === 'directo' ? '.' : ($calif === 'sugerencia' ? 's' : 'F');
}
echo "\n";

$n = count($detalle);
$cuenta = ['directo' => 0, 'sugerencia' => 0, 'fallo' => 0];
$porResultado = ['respondida' => 0, 'sugerencias' => 0, 'sin_respuesta' => 0, 'no_ofertada' => 0];
$matriz = [];
foreach ($detalle as $d) {
    $cuenta[$d['calificacion']]++;
    $porResultado[$d['resultado']] = ($porResultado[$d['resultado']] ?? 0) + 1;
    $c = $d['categoria'];
    if (!isset($matriz[$c])) {
        $matriz[$c] = ['total' => 0, 'directo' => 0, 'sugerencia' => 0, 'fallo' => 0];
    }
    $matriz[$c]['total']++;
    $matriz[$c][$d['calificacion']]++;
}
ksort($matriz);
$tiempos = array_column($detalle, 'ms');
$p50 = percentil($tiempos, 50);
$p95 = percentil($tiempos, 95);
$max = $tiempos ? max($tiempos) : 0.0;
$pctDirecto = $n ? 100 * $cuenta['directo'] / $n : 0.0;
$pctConSug = $n ? 100 * ($cuenta['directo'] + $cuenta['sugerencia']) / $n : 0.0;
$cumple = $n >= 100 ? ($pctDirecto >= 85 && $max < 2000 ? 'sí' : 'no') : 'no aplica (se requieren al menos 100 preguntas reales)';

$resumen = [
    'archivo'                 => relativa(realpath($archivo) ?: $archivo, $raiz),
    'nombre'                  => $nombre,
    'total'                   => $n,
    'aciertos_directos'       => $cuenta['directo'],
    'aciertos_en_sugerencia'  => $cuenta['sugerencia'],
    'fallos'                  => $cuenta['fallo'],
    'pct_directos'            => round($pctDirecto, 2),
    'pct_con_sugerencias'     => round($pctConSug, 2),
    'resultados'              => $porResultado,
    'tiempo_ms'               => ['p50' => round($p50, 2), 'p95' => round($p95, 2), 'max' => round($max, 2), 'promedio' => $n ? round(array_sum($tiempos) / $n, 2) : 0],
    'criterio_85_y_2s'        => $cumple,
];

// --------------------------------------------------------------- informe Markdown
$md = "# Evaluación del buscador: $nombre\n\n";
$md .= "| Dato | Valor |\n|---|---|\n";
$md .= "| Fecha | {$entorno['fecha']} |\n| Archivo de preguntas | `{$resumen['archivo']}` |\n";
$md .= "| Versión de PHP | {$entorno['php']} ({$entorno['so']}) |\n| Base de conocimiento | $descFuente ({$entorno['preguntas_base']} preguntas) |\n";
$md .= "| Configuración | `{$entorno['config']}` |\n";
$md .= sprintf("| Umbrales | respuesta %.2f · sugerencias %.2f · máx. sugerencias %d |\n\n", $umbrales['umbral_respuesta'], $umbrales['umbral_sugerencias'], $umbrales['max_sugerencias']);
if ($avisoFuente !== '') {
    $md .= "> Aviso: $avisoFuente\n\n";
}
$md .= "## Resumen\n\n| Medida | Valor |\n|---|---|\n";
$md .= "| Consultas evaluadas | $n |\n";
$md .= '| Aciertos directos | ' . $cuenta['directo'] . ' (' . pct($cuenta['directo'], $n) . ") |\n";
$md .= '| Aciertos en sugerencia (primera opción) | ' . $cuenta['sugerencia'] . ' (' . pct($cuenta['sugerencia'], $n) . ") |\n";
$md .= '| Aciertos incluyendo sugerencias | ' . ($cuenta['directo'] + $cuenta['sugerencia']) . ' (' . pct($cuenta['directo'] + $cuenta['sugerencia'], $n) . ") |\n";
$md .= '| Fallos | ' . $cuenta['fallo'] . ' (' . pct($cuenta['fallo'], $n) . ") |\n";
$md .= sprintf("| Tiempo por consulta p50 / p95 / máx | %.1f / %.1f / %.1f ms |\n", $p50, $p95, $max);
$md .= "| Cumple «≥ 85 % de aciertos directos y < 2 s» | $cumple |\n\n";
$md .= "El tiempo incluye cargar la base de conocimiento, construir el índice y buscar (lo mismo que hace cada petición al endpoint), "
    . "sin la latencia de red.\n\n";
$md .= "Un acierto directo es: respondida con un slug esperado, o `sin_respuesta` / `no_ofertada` cuando eso se esperaba. "
    . "Un acierto en sugerencia es cuando el motor ofrece sugerencias y la primera es la esperada.\n\n";
$md .= "### Resultados devueltos\n\n| Resultado | Consultas |\n|---|---|\n";
foreach ($porResultado as $k => $v) {
    $md .= "| $k | $v |\n";
}
$md .= "\n## Matriz por categoría\n\n| Categoría | Total | Directos | En sugerencia | Fallos | % directos | % con sugerencias |\n|---|---|---|---|---|---|---|\n";
foreach ($matriz as $c => $m) {
    $md .= '| ' . celda($c) . " | {$m['total']} | {$m['directo']} | {$m['sugerencia']} | {$m['fallo']} | " . pct($m['directo'], $m['total']) . ' | ' . pct($m['directo'] + $m['sugerencia'], $m['total']) . " |\n";
}
$fallos = array_values(array_filter($detalle, static function ($d) {
    return $d['calificacion'] === 'fallo';
}));
$md .= "\n## Fallos (" . count($fallos) . ")\n\n";
if ($fallos) {
    $md .= "| Fila | Consulta | Grupo | Esperado | Obtenido | Confianza |\n|---|---|---|---|---|---|\n";
    foreach ($fallos as $d) {
        $md .= "| {$d['fila']} | " . celda($d['consulta']) . ' | ' . celda($d['grupo']) . ' | ' . celda($d['esperado']) . ' | ' . celda($d['obtenido']) . ' | ' . number_format((float) $d['confianza'], 3) . " |\n";
    }
} else {
    $md .= "Ninguno.\n";
}
$enSug = array_values(array_filter($detalle, static function ($d) {
    return $d['calificacion'] === 'sugerencia';
}));
if ($enSug) {
    $md .= "\n## Aciertos en sugerencia (" . count($enSug) . ")\n\n| Fila | Consulta | Esperado | Obtenido | Confianza |\n|---|---|---|---|---|\n";
    foreach ($enSug as $d) {
        $md .= "| {$d['fila']} | " . celda($d['consulta']) . ' | ' . celda($d['esperado']) . ' | ' . celda($d['obtenido']) . ' | ' . number_format((float) $d['confianza'], 3) . " |\n";
    }
}
if ($avisos) {
    $md .= "\n## Avisos\n\n- " . implode("\n- ", array_map('celda', array_unique($avisos))) . "\n";
}

// --------------------------------------------------------------- consola y archivos
echo "\n";
echo "Consultas:                 $n\n";
echo 'Aciertos directos:         ' . $cuenta['directo'] . ' (' . pct($cuenta['directo'], $n) . ")\n";
echo 'Aciertos en sugerencia:    ' . $cuenta['sugerencia'] . "\n";
echo 'Incluyendo sugerencias:    ' . ($cuenta['directo'] + $cuenta['sugerencia']) . ' (' . pct($cuenta['directo'] + $cuenta['sugerencia'], $n) . ")\n";
echo 'Fallos:                    ' . $cuenta['fallo'] . "\n";
printf("Tiempo p50 / p95 / máx:    %.1f / %.1f / %.1f ms\n", $p50, $p95, $max);
echo "Criterio ≥85 % y < 2 s:     $cumple\n";
foreach ($fallos as $d) {
    printf("  FALLO fila %d: \"%s\" esperado=%s obtenido=%s (%.3f)\n", $d['fila'], $d['consulta'], $d['esperado'], $d['obtenido'], $d['confianza']);
}
foreach (array_unique($avisos) as $a) {
    echo "  AVISO: $a\n";
}

if (!$op['sin-informe']) {
    $base = 'evaluacion-' . Texto::slug($nombre) . '-' . date('Y-m-d_His');
    list($a, $b) = escribirInforme((string) $op['salida'], $base, $md, ['entorno' => $entorno, 'resumen' => $resumen, 'matriz' => $matriz, 'avisos' => array_values(array_unique($avisos)), 'consultas' => $detalle]);
    echo "\nInforme: " . relativa($a, $raiz) . "\n         " . relativa($b, $raiz) . "\n";
}

if ($op['minimo'] !== '' && $pctDirecto < (float) $op['minimo']) {
    exit(1);
}
exit(0);
