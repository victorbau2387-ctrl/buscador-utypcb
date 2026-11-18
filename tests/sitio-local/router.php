<?php
/**
 * Router para el servidor embebido de PHP (solo desarrollo local).
 *
 *   php -S 127.0.0.1:8080 -t tests/sitio-local tests/sitio-local/router.php
 *
 * /buscador/*  → public_html/buscador/*  (widget.css, widget.js, api.php)
 * lo demás     → archivos de tests/sitio-local (index.php, api-simulada.php)
 */
$ruta = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$ruta = rawurldecode($ruta);

if (strpos($ruta, '/buscador/') === 0) {
    $publico = realpath(__DIR__ . '/../../public_html/buscador');
    $relativo = substr($ruta, strlen('/buscador/'));
    $archivo = $publico !== false ? realpath($publico . '/' . $relativo) : false;
    // Carpetas (p. ej. /buscador/admin/) → su index.php, como hace Apache.
    if ($archivo !== false && is_dir($archivo)) {
        $archivo = realpath($archivo . '/index.php');
    }

    // Sin salir de public_html/buscador y sin archivos internos (_ruta_privada.php).
    if ($archivo === false || strpos($archivo, $publico . DIRECTORY_SEPARATOR) !== 0 || !is_file($archivo)
        || strpos(basename($archivo), '_') === 0) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'No encontrado';
        return true;
    }

    $ext = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
    if ($ext === 'php') {
        $_SERVER['SCRIPT_FILENAME'] = $archivo;
        $_SERVER['SCRIPT_NAME'] = $ruta;
        // Directorio de trabajo = raíz del repositorio, para que BF_CONFIG=tests/config.pruebas.php
        // (ruta relativa) se encuentre. api.php usa __DIR__ para sus require, no depende del cwd.
        chdir(dirname(__DIR__, 2));
        require $archivo;
        return true;
    }

    $tipos = ['css' => 'text/css; charset=utf-8', 'js' => 'text/javascript; charset=utf-8', 'svg' => 'image/svg+xml'];
    if (!isset($tipos[$ext])) {
        http_response_code(404);
        return true;
    }
    header('Content-Type: ' . $tipos[$ext]);
    header('Cache-Control: no-cache');
    readfile($archivo);
    return true;
}

// Archivos del sitio de prueba: que los sirva el servidor embebido.
return false;
