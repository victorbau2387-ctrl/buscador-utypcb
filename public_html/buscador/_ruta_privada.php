<?php
// Ubicación de la carpeta private/. En el hosting queda al lado de public_html:
//   /home/USUARIO/private/   y   /home/USUARIO/public_html/buscador/
// Ajusta solo si la instalas en otra ruta.
if (!defined('BF_RUTA_PRIVADA')) {
    $bfEnv = getenv('BF_RUTA_PRIVADA');
    define('BF_RUTA_PRIVADA', $bfEnv ? $bfEnv : dirname(__DIR__, 2) . '/private');
}
