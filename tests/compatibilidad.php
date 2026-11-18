<?php
// Comprobación de compatibilidad con PHP 7.4 (el hosting usa 7.4 por defecto y migrará a 8.x).
//
//   php tests/compatibilidad.php
//   php tests/compatibilidad.php --php74=C:\ruta\php74\php.exe
//
// 1) Busca funciones y sintaxis exclusivas de PHP 8 en private/, public_html/ y tests/
//    (ignorando comentarios y cadenas).
// 2) Ejecuta "php -l" con PHP 7.4 sobre cada archivo (si se encuentra el ejecutable).
// Código de salida: 0 sin problemas · 1 hay hallazgos o errores de sintaxis.
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit;
}

$raiz = dirname(__DIR__);
$php74 = getenv('PHP74') ?: 'C:\\Users\\Usuario\\dev-tools\\php74\\php.exe';
foreach (array_slice($argv, 1) as $a) {
    if (strpos($a, '--php74=') === 0) {
        $php74 = substr($a, 8);
    }
}
if (PHP_VERSION_ID < 80000) {
    $php74 = PHP_BINARY; // ya estamos en 7.x
}

/** @return string[] */
function archivosPhp(string $raiz): array
{
    $res = [];
    foreach (['private', 'public_html', 'tests'] as $dir) {
        if (!is_dir($raiz . '/' . $dir)) {
            continue;
        }
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz . '/' . $dir, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile() && strtolower($f->getExtension()) === 'php') {
                $res[] = str_replace('\\', '/', $f->getPathname());
            }
        }
    }
    sort($res);
    return $res;
}

/**
 * Código sin comentarios ni contenido de cadenas, conservando los saltos de línea
 * (para informar el número de línea correcto).
 */
function codigoLimpio(string $fuente): string
{
    $out = '';
    foreach (token_get_all($fuente) as $t) {
        if (is_string($t)) {
            $out .= $t;
            continue;
        }
        list($id, $texto) = $t;
        if (defined('T_ATTRIBUTE') && $id === constant('T_ATTRIBUTE')) {
            $out .= '#[ATRIBUTO]';
        } elseif ($id === T_COMMENT || $id === T_DOC_COMMENT) {
            // Excepción: en PHP 7.4 un atributo "#[...]" se tokeniza como comentario.
            if (strpos($texto, '#[') === 0) {
                $out .= '#[ATRIBUTO]' . str_repeat("\n", substr_count($texto, "\n"));
                continue;
            }
            $out .= str_repeat("\n", substr_count($texto, "\n"));
        } elseif ($id === T_CONSTANT_ENCAPSED_STRING || $id === T_ENCAPSED_AND_WHITESPACE || $id === T_INLINE_HTML) {
            $out .= "''" . str_repeat("\n", substr_count($texto, "\n"));
        } else {
            $out .= $texto;
        }
    }
    return $out;
}

/** Patrones de PHP 8 que no existen en 7.4. */
const PATRONES = [
    'str_contains() (PHP 8.0)'               => '/(?<![\w>$:\\\\])str_contains\s*\(/i',
    'str_starts_with() (PHP 8.0)'            => '/(?<![\w>$:\\\\])str_starts_with\s*\(/i',
    'str_ends_with() (PHP 8.0)'              => '/(?<![\w>$:\\\\])str_ends_with\s*\(/i',
    'otras funciones de PHP 8'               => '/(?<![\w>$:\\\\])(array_is_list|get_debug_type|get_resource_id|fdiv|preg_last_error_msg|enum_exists|array_find|array_any|array_all|mb_str_pad|json_validate)\s*\(/i',
    'match (PHP 8.0)'                        => '/(?<![\w>$:\\\\])match\s*\(/i',
    'enum (PHP 8.1)'                         => '/(^|[;{}])\s*enum\s+[A-Za-z_]\w*/m',
    'readonly (PHP 8.1)'                     => '/(?<![\w$])readonly\s+(?!\()/i',
    'operador nullsafe ?-> (PHP 8.0)'        => '/\?->/',
    'argumento con nombre (PHP 8.0)'         => '/[\w\)]\s*\(\s*(?:[^()]*?,\s*)?[a-z_]\w*\s*:(?!:)\s*[\$\'"\[\w-]/i',
    'promoción en constructor (PHP 8.0)'     => '/function\s+__construct\s*\([^)]*\b(public|protected|private|readonly)\s/i',
    'tipo mixed (PHP 8.0)'                   => '/([(,]\s*\??mixed\s+&?\$|\)\s*:\s*\??mixed\b)/i',
    'tipo unión (PHP 8.0)'                   => '/([(,]\s*\??[\w\\\\]+\s*\|\s*[\w\\\\|]+\s+&?\$|\)\s*:\s*\??[\w\\\\]+\s*\|)/',
    'tipo de retorno static/never (8.0/8.1)' => '/\)\s*:\s*(static|never)\b/i',
    'throw como expresión (PHP 8.0)'         => '/(\?\?|\?:|=>|\?)\s*throw\b/',
    'catch sin variable (PHP 8.0)'           => '/catch\s*\(\s*[\w\\\\|\s]+\s*\)/',
    'atributo #[...] (PHP 8.0)'              => '/#\[ATRIBUTO\]/',
    'new en inicializador (PHP 8.1)'         => '/function\s+\w+\s*\([^)]*=\s*new\s/i',
    'first-class callable f(...) (PHP 8.1)'  => '/\w\s*\(\s*\.\.\.\s*\)/',
];

$archivos = archivosPhp($raiz);
$hallazgos = [];
foreach ($archivos as $ruta) {
    $codigo = codigoLimpio((string) file_get_contents($ruta));
    $lineas = explode("\n", $codigo);
    foreach ($lineas as $n => $linea) {
        foreach (PATRONES as $nombre => $re) {
            if ($nombre === 'argumento con nombre (PHP 8.0)') {
                // Evita falsos positivos: operador ternario, case, etiquetas y tipos de retorno.
                if (strpos($linea, '?') !== false || preg_match('/\b(case|default)\b/', $linea) || preg_match('/function\s/', $linea)) {
                    continue;
                }
            }
            if (preg_match($re, $linea)) {
                $hallazgos[] = sprintf('%s:%d  %s  →  %s', substr($ruta, strlen(str_replace('\\', '/', $raiz)) + 1), $n + 1, $nombre, trim($linea));
            }
        }
    }
}

echo "Compatibilidad PHP 7.4 — " . count($archivos) . " archivos .php revisados (ejecutando con PHP " . PHP_VERSION . ")\n\n";
echo "1) Sintaxis y funciones exclusivas de PHP 8:\n";
if ($hallazgos) {
    foreach ($hallazgos as $h) {
        echo '   [FALLA] ' . $h . "\n";
    }
} else {
    echo "   [ok] ninguna\n";
}

echo "\n2) php -l con PHP 7.4:\n";
$erroresLint = [];
$lintOmitido = false;
if (!is_file($php74)) {
    $lintOmitido = true;
    echo "   [omitido] no se encontró PHP 7.4 en $php74 (usa --php74=ruta o la variable PHP74)\n";
} else {
    $version = trim((string) shell_exec(escapeshellarg($php74) . ' -r "echo PHP_VERSION;"'));
    echo "   usando $php74 (PHP $version)\n";
    if (strpos($version, '7.4') !== 0) {
        echo "   [aviso] el ejecutable no es PHP 7.4\n";
    }
    foreach ($archivos as $ruta) {
        $salida = [];
        $codigo = 0;
        exec(escapeshellarg($php74) . ' -l ' . escapeshellarg($ruta) . ' 2>&1', $salida, $codigo);
        if ($codigo !== 0) {
            $erroresLint[] = implode(' ', $salida);
        }
    }
    if ($erroresLint) {
        foreach ($erroresLint as $e) {
            echo '   [FALLA] ' . $e . "\n";
        }
    } else {
        echo '   [ok] ' . count($archivos) . " archivos sin errores de sintaxis\n";
    }
}

$total = count($hallazgos) + count($erroresLint);
echo "\n" . str_repeat('-', 70) . "\n";
echo $total === 0
    ? 'RESULTADO: OK' . ($lintOmitido ? ' (sin php -l 7.4)' : '') . "\n"
    : "RESULTADO: $total problema(s) de compatibilidad\n";
exit($total === 0 ? 0 : 1);
