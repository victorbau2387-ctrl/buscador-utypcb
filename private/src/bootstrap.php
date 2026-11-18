<?php
// Punto de arranque común. Compatible con PHP 7.4 y 8.x (sin str_contains, match, enums, etc.).
declare(strict_types=1);

if (PHP_VERSION_ID < 70400) {
    http_response_code(500);
    exit('Se requiere PHP 7.4 o superior.');
}

define('BF_PRIVATE', dirname(__DIR__));

spl_autoload_register(static function (string $clase): void {
    $prefijo = 'Buscador\\';
    if (strncmp($clase, $prefijo, strlen($prefijo)) !== 0) {
        return;
    }
    $ruta = __DIR__ . '/' . str_replace('\\', '/', substr($clase, strlen($prefijo))) . '.php';
    if (is_file($ruta)) {
        require_once $ruta;
    }
});

mb_internal_encoding('UTF-8');
date_default_timezone_set('America/Mexico_City');

\Buscador\Support\Config::cargar();

if (\Buscador\Support\Config::get('entorno') === 'desarrollo') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', BF_PRIVATE . '/logs/php-error.log');
}
