<?php
// Pruebas de seguridad del endpoint público (public_html/buscador/api.php).
//
// Modo por defecto: crea/actualiza la base de pruebas, levanta el servidor embebido de PHP
// con una configuración temporal y le hace peticiones HTTP reales.
//
//   php tests/endpoint.php
//   php tests/endpoint.php --db-host=127.0.0.1 --db-port=3307 --db-user=root --db-pass= --db-nombre=bf_pruebas
//   php tests/endpoint.php --url=https://staging.ejemplo/buscador/api.php --origen=https://staging.ejemplo --limite=20
//
// Con --url no se levanta servidor: se prueba una instalación existente (no se reinician los contadores
// de límite, así que la ráfaga solo comprueba que en algún momento aparece 429).
//
// Código de salida: 0 todo bien · 1 hay fallos · 2 OMITIDO (no hay MariaDB disponible).
// No usa ninguna base distinta de la indicada en --db-nombre (por defecto bf_pruebas).
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit;
}

require __DIR__ . '/lib/aserciones.php';
mb_internal_encoding('UTF-8');

$raiz = dirname(__DIR__);
$op = [
    'url'       => '',
    'origen'    => 'https://utypcb.edu.mx',
    'limite'    => '8',
    'php'       => PHP_BINARY,
    'db-host'   => getenv('BF_DB_HOST') ?: '127.0.0.1',
    'db-port'   => getenv('BF_DB_PORT') ?: '3307',
    'db-user'   => getenv('BF_DB_USER') ?: 'root',
    'db-pass'   => getenv('BF_DB_PASS') ?: '',
    'db-nombre' => getenv('BF_DB_NOMBRE') ?: 'bf_pruebas',
];
foreach (array_slice($argv, 1) as $a) {
    if (preg_match('/^--([a-z-]+)=(.*)$/', $a, $m) && array_key_exists($m[1], $op)) {
        $op[$m[1]] = $m[2];
    } elseif ($a === '--ayuda' || $a === '-h') {
        echo "Uso: php tests/endpoint.php [--url=...] [--origen=...] [--limite=N] [--db-host=..] [--db-port=..] [--db-user=..] [--db-pass=..] [--db-nombre=bf_pruebas]\n";
        exit(0);
    } else {
        fwrite(STDERR, "Opción desconocida: $a\n");
        exit(1);
    }
}
if (!preg_match('/^[a-zA-Z0-9_]+$/', $op['db-nombre'])) {
    fwrite(STDERR, "Nombre de base inválido.\n");
    exit(1);
}
$limite = max(1, (int) $op['limite']);
$origen = $op['origen'];

// ---------------------------------------------------------------- base de datos
$pdo = null;
try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $op['db-host'], (int) $op['db-port']),
        $op['db-user'],
        $op['db-pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_TIMEOUT => 3]
    );
} catch (Throwable $e) {
    $pdo = null;
    if ($op['url'] === '') {
        echo "OMITIDO: no hay MariaDB en {$op['db-host']}:{$op['db-port']} ({$e->getMessage()}).\n";
        echo "Las pruebas del endpoint necesitan una base de datos (límite de solicitudes y registro de consultas).\n";
        echo "Indica otra con --db-host/--db-port/--db-user/--db-pass o prueba un servidor ya instalado con --url=.\n";
        exit(2);
    }
}

/** Crea las tablas desde database/schema.sql y carga las preguntas del fixture. */
function prepararBase(PDO $pdo, string $nombre, string $raiz): void
{
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$nombre` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$nombre`");
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach (['bf_consulta', 'bf_variante', 'bf_faq', 'bf_area', 'bf_admin', 'bf_limite'] as $t) {
        $pdo->exec("DROP TABLE IF EXISTS `$t`");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    $sql = (string) file_get_contents($raiz . '/database/schema.sql');
    $sql = (string) preg_replace('/^\s*--.*$/m', '', $sql);
    foreach (preg_split('/;\s*(\r?\n|$)/', $sql) ?: [] as $sentencia) {
        if (trim($sentencia) !== '') {
            $pdo->exec($sentencia);
        }
    }
    $dir = $raiz . '/tests/fixtures/conocimiento';
    $areas = [];
    foreach (leerCsvSimple($dir . '/areas.csv') as $a) {
        $pdo->prepare('INSERT INTO bf_area (clave, nombre, correo, telefono, url) VALUES (?, ?, ?, ?, ?)')
            ->execute([$a['clave'], $a['nombre'], $a['correo'] ?: null, $a['telefono'] ?: null, $a['url'] ?: null]);
        $areas[$a['clave']] = (int) $pdo->lastInsertId();
    }
    $insFaq = $pdo->prepare(
        'INSERT INTO bf_faq (slug, categoria, pregunta, respuesta, tipo, handler, palabras_clave, carrera, fuente_url, area_id, estado)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $insVar = $pdo->prepare('INSERT INTO bf_variante (faq_id, texto) VALUES (?, ?)');
    foreach (leerCsvSimple($dir . '/preguntas.csv') as $f) {
        $insFaq->execute([
            $f['slug'], $f['categoria'], $f['pregunta'], $f['respuesta'], $f['tipo'] ?: 'fija', $f['handler'] ?: null,
            $f['palabras_clave'], $f['carrera'] ?: null, $f['fuente_url'], $areas[$f['area']] ?? null, $f['estado'] ?: 'borrador',
        ]);
        $id = (int) $pdo->lastInsertId();
        foreach (array_filter(array_map('trim', explode('|', $f['variantes'])), 'strlen') as $v) {
            $insVar->execute([$id, $v]);
        }
    }
}

/** @return array<int,array<string,string>> */
function leerCsvSimple(string $ruta): array
{
    $fh = fopen($ruta, 'rb');
    $enc = fgetcsv($fh, 0, ',', '"', '\\');
    $enc[0] = (string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $enc[0]);
    $filas = [];
    while (($f = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
        if ($f === [null]) {
            continue;
        }
        $filas[] = array_combine($enc, array_pad($f, count($enc), ''));
    }
    fclose($fh);
    return $filas;
}

// ---------------------------------------------------------------- servidor
$servidor = null;
$logServidor = null;
$configTmp = null;
$url = $op['url'];

if ($url === '') {
    prepararBase($pdo, $op['db-nombre'], $raiz);

    $configTmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bf-endpoint-config-' . getmypid() . '.php';
    $config = [
        'entorno'   => 'produccion',
        'db'        => ['host' => $op['db-host'], 'puerto' => (int) $op['db-port'], 'nombre' => $op['db-nombre'], 'usuario' => $op['db-user'], 'password' => $op['db-pass']],
        'seguridad' => ['origenes_permitidos' => [$origen], 'max_longitud' => 300, 'limite_por_minuto' => $limite, 'sal_hash' => bin2hex(random_bytes(16))],
        'sitio'     => ['ruta_datos' => $raiz . '/tests/fixtures/site_data', 'url_base' => 'https://utypcb.edu.mx'],
    ];
    file_put_contents(
        $configTmp,
        "<?php\n\$base = require " . var_export($raiz . '/private/config.example.php', true) . ";\nreturn array_replace_recursive(\$base, "
        . var_export($config, true) . ");\n"
    );

    $s = stream_socket_server('tcp://127.0.0.1:0');
    $nombre = stream_socket_get_name($s, false);
    fclose($s);
    $puerto = (int) substr((string) $nombre, strrpos((string) $nombre, ':') + 1);

    $logServidor = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bf-endpoint-servidor-' . getmypid() . '.log';
    $env = getenv();
    $env['BF_CONFIG'] = $configTmp;
    $servidor = proc_open(
        [$op['php'], '-S', '127.0.0.1:' . $puerto, '-t', $raiz . '/public_html'],
        [0 => ['pipe', 'r'], 1 => ['file', $logServidor, 'a'], 2 => ['file', $logServidor, 'a']],
        $tuberias,
        $raiz,
        $env
    );
    if (!is_resource($servidor)) {
        echo "No se pudo levantar el servidor embebido.\n";
        exit(1);
    }
    register_shutdown_function(static function () use (&$servidor, $configTmp, $logServidor) {
        if (is_resource($servidor)) {
            proc_terminate($servidor);
            proc_close($servidor);
        }
        if ($configTmp !== null && is_file($configTmp)) {
            unlink($configTmp);
        }
        if ($logServidor !== null && is_file($logServidor)) {
            @unlink($logServidor);
        }
    });
    $listo = false;
    for ($i = 0; $i < 50 && !$listo; $i++) {
        $c = @fsockopen('127.0.0.1', $puerto, $errno, $errstr, 0.2);
        if ($c) {
            fclose($c);
            $listo = true;
        } else {
            usleep(100000);
        }
    }
    if (!$listo) {
        echo "El servidor embebido no respondió. Registro:\n" . @file_get_contents($logServidor) . "\n";
        exit(1);
    }
    $url = 'http://127.0.0.1:' . $puerto . '/buscador/api.php';
    echo "Servidor embebido en $url (PHP " . trim((string) shell_exec(escapeshellarg($op['php']) . ' -r "echo PHP_VERSION;"')) . ")\n";
    echo "Base de pruebas: {$op['db-nombre']} en {$op['db-host']}:{$op['db-port']} · límite por minuto: $limite\n";
} else {
    echo "Probando instalación existente: $url · origen permitido: $origen · límite esperado: $limite\n";
    if ($pdo !== null) {
        try {
            $pdo->exec("USE `{$op['db-nombre']}`");
        } catch (Throwable $e) {
            $pdo = null;
        }
    }
}

// ---------------------------------------------------------------- cliente HTTP

/**
 * @param array<string,string> $cabeceras
 * @return array{estado:int,cabeceras:array<string,string>,cuerpo:string,json:mixed}
 */
function peticion(string $metodo, string $url, ?string $cuerpo = null, array $cabeceras = []): array
{
    $h = [];
    foreach ($cabeceras as $k => $v) {
        $h[] = "$k: $v";
    }
    $ctx = stream_context_create(['http' => [
        'method'        => $metodo,
        'header'        => implode("\r\n", $h),
        'content'       => $cuerpo ?? '',
        'ignore_errors' => true,
        'timeout'       => 15,
    ]]);
    $http_response_header = [];
    $resp = @file_get_contents($url, false, $ctx);
    $estado = 0;
    $res = [];
    foreach ($http_response_header as $linea) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $linea, $m)) {
            $estado = (int) $m[1];
            $res = [];
        } elseif (strpos($linea, ':') !== false) {
            list($k, $v) = explode(':', $linea, 2);
            $res[strtolower(trim($k))] = trim($v);
        }
    }
    $cuerpoResp = $resp === false ? '' : $resp;
    return ['estado' => $estado, 'cabeceras' => $res, 'cuerpo' => $cuerpoResp, 'json' => json_decode($cuerpoResp, true)];
}

/** POST JSON con el origen permitido (o el indicado). */
function post(string $url, $datos, ?string $origen, bool $crudo = false): array
{
    $cab = ['Content-Type' => 'application/json'];
    if ($origen !== null) {
        $cab['Origin'] = $origen;
    }
    return peticion('POST', $url, $crudo ? (string) $datos : (string) json_encode($datos, JSON_UNESCAPED_UNICODE), $cab);
}

function afirmarJsonSeguro(array $r, string $donde): void
{
    coincide('#^application/json#i', $r['cabeceras']['content-type'] ?? '', "$donde: Content-Type");
    igual('nosniff', strtolower($r['cabeceras']['x-content-type-options'] ?? ''), "$donde: X-Content-Type-Options");
    igual('no-store', strtolower($r['cabeceras']['cache-control'] ?? ''), "$donde: Cache-Control");
    afirmar(is_array($r['json']), "$donde: el cuerpo debe ser JSON válido: " . exportar($r['cuerpo']));
}

$reiniciarLimite = static function () use (&$pdo, $op): bool {
    if ($pdo === null || $op['url'] !== '') {
        return false;
    }
    $pdo->exec('DELETE FROM bf_limite');
    return true;
};

// ---------------------------------------------------------------- pruebas
grupo('Endpoint: método, origen y formato');

prueba('GET → 405 con JSON y nosniff', function () use ($url) {
    $r = peticion('GET', $url);
    igual(405, $r['estado']);
    afirmarJsonSeguro($r, 'GET');
    igual(false, $r['json']['ok']);
});

prueba('PUT → 405', function () use ($url, $origen) {
    $r = peticion('PUT', $url, '{}', ['Content-Type' => 'application/json', 'Origin' => $origen]);
    igual(405, $r['estado']);
});

prueba('origen no permitido → 403 sin cabecera CORS', function () use ($url) {
    $r = post($url, ['accion' => 'buscar', 'consulta' => 'becas'], 'https://sitio-ajeno.example');
    igual(403, $r['estado']);
    afirmarJsonSeguro($r, '403');
    afirmar(!isset($r['cabeceras']['access-control-allow-origin']), 'no debe enviar Access-Control-Allow-Origin');
});

prueba('origen parecido pero distinto (subdominio/puerto/esquema) → 403', function () use ($url, $origen) {
    foreach ([$origen . '.evil.example', str_replace('https://', 'http://', $origen), $origen . ':8443', 'null'] as $o) {
        igual(403, post($url, ['consulta' => 'becas'], $o)['estado'], $o);
    }
});

prueba('preflight OPTIONS del origen permitido → 204 con CORS restringido (nunca *)', function () use ($url, $origen) {
    $r = peticion('OPTIONS', $url, null, ['Origin' => $origen, 'Access-Control-Request-Method' => 'POST']);
    igual(204, $r['estado']);
    igual($origen, $r['cabeceras']['access-control-allow-origin'] ?? null);
    contiene('POST', $r['cabeceras']['access-control-allow-methods'] ?? '');
});

prueba('JSON inválido → 400', function () use ($url, $origen, $reiniciarLimite) {
    $reiniciarLimite();
    foreach (['{no es json', '', '123', '"texto"', 'null'] as $cuerpo) {
        $r = post($url, $cuerpo, $origen, true);
        igual(400, $r['estado'], exportar($cuerpo));
        afirmarJsonSeguro($r, '400');
    }
});

prueba('cuerpo enorme (> 8 KB) → 400, no 500', function () use ($url, $origen) {
    $r = post($url, ['accion' => 'buscar', 'consulta' => str_repeat('a', 20000)], $origen);
    igual(400, $r['estado']);
});

prueba('acción desconocida → 400', function () use ($url, $origen, $reiniciarLimite) {
    $reiniciarLimite();
    $r = post($url, ['accion' => 'borrar_todo'], $origen);
    igual(400, $r['estado']);
    afirmarJsonSeguro($r, 'acción');
});

grupo('Endpoint: validación de la consulta');

prueba('consulta vacía o solo espacios → 422', function () use ($url, $origen, $reiniciarLimite) {
    $reiniciarLimite();
    foreach (['', '   ', "\n\t", '<b></b>'] as $q) {
        $r = post($url, ['accion' => 'buscar', 'consulta' => $q], $origen);
        igual(422, $r['estado'], exportar($q));
        afirmarJsonSeguro($r, '422');
    }
});

prueba('consulta de 301 caracteres → 422; de 300 (multibyte) → 200', function () use ($url, $origen, $reiniciarLimite) {
    $reiniciarLimite();
    igual(422, post($url, ['accion' => 'buscar', 'consulta' => str_repeat('a', 301)], $origen)['estado']);
    igual(200, post($url, ['accion' => 'buscar', 'consulta' => str_repeat('ñ', 300)], $origen)['estado'], '300 ñ cuentan como 300');
});

prueba('consulta que no es texto no rompe el servicio', function () use ($url, $origen, $reiniciarLimite) {
    $reiniciarLimite();
    foreach ([['a' => 1], 12345, true] as $q) {
        $r = post($url, ['accion' => 'buscar', 'consulta' => $q, 'contexto' => 'no-arreglo'], $origen);
        afirmar($r['estado'] < 500, 'estado ' . $r['estado'] . ' para ' . exportar($q));
    }
});

prueba('consulta que no es texto (arreglo) → 422', function () use ($url, $origen, $reiniciarLimite) {
        $reiniciarLimite();
        igual(422, post($url, ['accion' => 'buscar', 'consulta' => ['a' => 1]], $origen)['estado']);
    }
);

grupo('Endpoint: respuestas válidas');

prueba('búsqueda válida → 200 con resultado, CORS del origen y consulta_id', function () use ($url, $origen, $reiniciarLimite) {
    $reiniciarLimite();
    $r = post($url, ['accion' => 'buscar', 'consulta' => 'cuanto cuesta la inscripcion', 'contexto' => ['carrera' => null]], $origen);
    igual(200, $r['estado'], $r['cuerpo']);
    afirmarJsonSeguro($r, '200');
    igual(true, $r['json']['ok']);
    igual('respondida', $r['json']['resultado']);
    igual($origen, $r['cabeceras']['access-control-allow-origin'] ?? null);
    afirmar(!array_key_exists('diagnostico', $r['json']), 'el diagnóstico interno no se expone');
    afirmar(is_int($r['json']['consulta_id'] ?? null) || ($r['json']['consulta_id'] ?? null) === null, 'consulta_id');
    afirmar((int) $r['json']['ms'] < 2000, 'tiempo del motor < 2 s');
});

prueba('sin cabecera Origin (mismo sitio) → 200', function () use ($url) {
    $r = post($url, ['accion' => 'buscar', 'consulta' => 'becas'], null);
    igual(200, $r['estado']);
});

prueba('acciones ver / util / inicio', function () use ($url, $origen, $reiniciarLimite) {
    $reiniciarLimite();
    $b = post($url, ['accion' => 'buscar', 'consulta' => 'becas'], $origen)['json'];
    $v = post($url, ['accion' => 'ver', 'id' => $b['faq_id']], $origen);
    igual(200, $v['estado']);
    igual($b['faq_id'], $v['json']['faq_id']);
    igual(404, post($url, ['accion' => 'ver', 'id' => 999999], $origen)['estado']);
    igual(200, post($url, ['accion' => 'util', 'consulta_id' => (int) ($b['consulta_id'] ?? 0), 'util' => 1], $origen)['estado']);
    $i = post($url, ['accion' => 'inicio'], $origen);
    igual(200, $i['estado']);
    afirmar(is_array($i['json']['sugerencias']) && count($i['json']['sugerencias']) <= 4, 'sugerencias de inicio');
});

prueba('tiempo total de respuesta HTTP < 2 s', function () use ($url, $origen, $reiniciarLimite) {
    $reiniciarLimite();
    $t = microtime(true);
    $r = post($url, ['accion' => 'buscar', 'consulta' => 'kiero saber los rekisitos de admision'], $origen);
    $ms = (microtime(true) - $t) * 1000;
    igual(200, $r['estado']);
    afirmar($ms < 2000, sprintf('%.0f ms', $ms));
});

grupo('Endpoint: XSS y privacidad');

prueba('intento de XSS en la consulta no se refleja sin escapar', function () use ($url, $origen, $reiniciarLimite, &$pdo) {
    $reiniciarLimite();
    $ataques = [
        '<script>alert(1)</script> becas',
        '"><img src=x onerror=alert(1)> inscripcion',
        "<svg/onload=alert('x')>",
        'javascript:alert(1) <iframe src=//x>',
    ];
    foreach ($ataques as $q) {
        $r = post($url, ['accion' => 'buscar', 'consulta' => $q], $origen);
        afirmar(in_array($r['estado'], [200, 422], true), 'estado ' . $r['estado']);
        foreach (['<script', '<img', '<svg', '<iframe', 'onerror=', 'onload='] as $peligro) {
            noContiene($peligro, strtolower($r['cuerpo']), 'respuesta para ' . exportar($q));
        }
        if ($r['estado'] === 200) {
            afirmarJsonSeguro($r, 'XSS');
        }
    }
    if ($pdo !== null) {
        $filas = $pdo->query('SELECT consulta FROM bf_consulta')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($filas as $c) {
            noContiene('<script', strtolower((string) $c), 'registro en bf_consulta');
            noContiene('<img', strtolower((string) $c), 'registro en bf_consulta');
        }
    }
});

prueba('el registro enmascara correos y teléfonos y no guarda la IP', function () use ($url, $origen, $reiniciarLimite, &$pdo, $op) {
    if ($pdo === null || $op['url'] !== '') {
        echo "              (sin acceso a la base: se omite la verificación del registro)\n";
        return;
    }
    $reiniciarLimite();
    $r = post($url, ['accion' => 'buscar', 'consulta' => 'soy ana.lopez@correo.example mi cel 744 123 4567 cuanto cuesta la inscripcion'], $origen);
    igual(200, $r['estado']);
    $fila = $pdo->query('SELECT * FROM bf_consulta ORDER BY id DESC LIMIT 1')->fetch();
    contiene('[correo]', $fila['consulta']);
    contiene('[número]', $fila['consulta']);
    noContiene('ana.lopez', $fila['consulta']);
    noContiene('4567', $fila['consulta']);
    $columnas = $pdo->query('SHOW COLUMNS FROM bf_consulta')->fetchAll(PDO::FETCH_COLUMN);
    afirmar(!array_intersect(['ip', 'ip_hash', 'user_agent', 'sesion'], $columnas), 'bf_consulta no tiene columnas de identificación');
    foreach ($pdo->query('SELECT clave FROM bf_limite')->fetchAll(PDO::FETCH_COLUMN) as $clave) {
        coincide('/^[0-9a-f]{64}$/', (string) $clave, 'bf_limite guarda solo un hash');
    }
});

grupo('Endpoint: límite de solicitudes');

prueba('ráfaga > limite_por_minuto → 429', function () use ($url, $origen, $limite, $reiniciarLimite) {
    $exacto = $reiniciarLimite();
    if ($exacto && (time() % 60) > 50) {
        sleep(61 - (time() % 60)); // evita que la ráfaga cruce el cambio de minuto
        $reiniciarLimite();
    }
    $estados = [];
    for ($i = 0; $i < $limite + 1; $i++) {
        $r = post($url, ['accion' => 'buscar', 'consulta' => 'becas ' . $i], $origen);
        $estados[] = $r['estado'];
    }
    if ($exacto) {
        igual(array_fill(0, $limite, 200), array_slice($estados, 0, $limite), 'las primeras ' . $limite . ' pasan');
        igual(429, $estados[$limite], 'la número ' . ($limite + 1) . ' se rechaza');
    } else {
        enArreglo(429, $estados, 'debe aparecer 429 en ' . ($limite + 1) . ' solicitudes');
    }
    afirmarJsonSeguro($r, '429');
    igual(false, $r['json']['ok']);
});

$codigo = ejecutarPruebas();
if ($codigo !== 0 && $logServidor !== null && is_file($logServidor)) {
    echo "\nRegistro del servidor embebido:\n" . file_get_contents($logServidor) . "\n";
}
exit($codigo);
