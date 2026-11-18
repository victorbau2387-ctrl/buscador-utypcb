<?php
declare(strict_types=1);

namespace Buscador\Support;

final class Http
{
    /** Escapa texto para HTML. Usar en TODA salida de vistas. */
    public static function e(?string $texto): string
    {
        return htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** @param array<string,mixed> $datos */
    public static function json(array $datos, int $estado = 200): void
    {
        http_response_code($estado);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** CORS restringido a los orígenes configurados (nunca *). Devuelve false si el origen no está permitido. */
    public static function cors(): bool
    {
        $origen = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origen === '') {
            return true; // misma página (petición del propio sitio)
        }
        $permitidos = (array) Config::get('seguridad.origenes_permitidos', []);
        // Los navegadores envían Origin también en POST del mismo sitio: se acepta si coincide con el host servido.
        $hostOrigen = (string) parse_url($origen, PHP_URL_HOST) . (parse_url($origen, PHP_URL_PORT) ? ':' . parse_url($origen, PHP_URL_PORT) : '');
        $mismoSitio = $hostOrigen !== '' && strcasecmp($hostOrigen, (string) ($_SERVER['HTTP_HOST'] ?? '')) === 0;
        if (!$mismoSitio && !in_array($origen, $permitidos, true)) {
            return false;
        }
        if ($mismoSitio) {
            return true; // misma página: no hacen falta cabeceras CORS
        }
        header('Access-Control-Allow-Origin: ' . $origen);
        header('Vary: Origin');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        return true;
    }

    /** Identificador anónimo del visitante (hash con sal; la IP no se guarda). */
    public static function huellaVisitante(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return hash('sha256', (string) Config::get('seguridad.sal_hash', '') . '|' . $ip);
    }
}
