<?php
/**
 * Vista previa del widget SOBRE la portada real de utypcb.edu.mx (solo desarrollo local).
 *
 *   http://127.0.0.1:8080/replica.php            usa la copia en caché (se renueva cada 6 h)
 *   http://127.0.0.1:8080/replica.php?renovar=1  vuelve a descargar la portada
 *
 * Descarga la portada pública, agrega <base href="https://utypcb.edu.mx/"> para que estilos, imágenes
 * y enlaces sigan apuntando al sitio oficial, e inserta widget.php antes de </body>, como lo hará
 * footer.inc.php en producción. Así se ve el botón con las variables CSS, fuentes y scripts REALES.
 *
 * No forma parte del producto ni se sube al hosting: el buscador NUNCA lee el HTML del sitio
 * (sus datos vienen de los *_data.php). La copia descargada queda en tests/sitio-local/.cache/ (ignorada por git).
 */
declare(strict_types=1);

$origen = 'https://utypcb.edu.mx/';
$cache = __DIR__ . '/.cache/portada.html';
$renovar = isset($_GET['renovar']) || !is_file($cache) || filemtime($cache) < time() - 6 * 3600;

if ($renovar) {
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 15,
            'header'  => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36\r\n"
                . "Accept-Language: es-MX,es;q=0.9\r\n",
        ],
    ]);
    $html = @file_get_contents($origen, false, $ctx);
    if (is_string($html) && stripos($html, '</body>') !== false) {
        if (!is_dir(dirname($cache))) {
            mkdir(dirname($cache), 0775, true);
        }
        file_put_contents($cache, $html);
    }
}

if (!is_file($cache)) {
    http_response_code(502);
    header('Content-Type: text/plain; charset=utf-8');
    echo "No se pudo descargar la portada de $origen (¿sin internet?). Usa la página simulada: /";
    return;
}
$html = (string) file_get_contents($cache);

// El widget se sirve desde este servidor local: rutas absolutas para que <base> no las mande al sitio real.
$local = 'http://' . ($_SERVER['HTTP_HOST'] ?? '127.0.0.1:8080') . '/buscador/';
define('BF_WIDGET_BASE', $local);
define('BF_WIDGET_API', $local . 'api.php');
define('BF_WIDGET_ADMIN', $local . 'admin/');

ob_start();
require __DIR__ . '/../../public_html/buscador/widget.php';
$widget = (string) ob_get_clean();

$aviso = '<div style="position:fixed;left:12px;bottom:12px;z-index:8999;padding:6px 10px;border-radius:8px;'
    . 'background:#071a16;color:#eef3f1;font:12px/1.3 system-ui,sans-serif;opacity:.85">'
    . 'Vista previa local · portada de utypcb.edu.mx + buscador</div>';

// <base> justo después de <head> para que todo lo relativo resuelva al sitio oficial.
$html = (string) preg_replace('/<head([^>]*)>/i', '<head$1><base href="' . $origen . '">', $html, 1);
$pos = strripos($html, '</body>');
$html = substr($html, 0, (int) $pos) . $widget . $aviso . substr($html, (int) $pos);

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
echo $html;
