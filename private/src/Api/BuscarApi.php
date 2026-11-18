<?php
declare(strict_types=1);

namespace Buscador\Api;

use Buscador\Search\Buscador;
use Buscador\Search\Indice;
use Buscador\Search\Repositorio;
use Buscador\Search\Texto;
use Buscador\Support\Config;
use Buscador\Support\Db;
use Buscador\Support\Http;
use Buscador\Support\RateLimiter;
use Throwable;

/**
 * Endpoint público del widget. Solo POST con JSON.
 *
 *   POST api.php  {"accion":"buscar","consulta":"cuanto cuesta la inscripcion","contexto":{"carrera":null}}
 *   POST api.php  {"accion":"ver","id":12,"contexto":{...}}
 *   POST api.php  {"accion":"util","consulta_id":345,"util":1}
 *   POST api.php  {"accion":"inicio"}   → preguntas sugeridas para mostrar al abrir
 */
final class BuscarApi
{
    public static function atender(): void
    {
        try {
            if (!Http::cors()) {
                Http::json(['ok' => false, 'error' => 'Origen no permitido.'], 403);
                return;
            }
            $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            if ($metodo === 'OPTIONS') {
                http_response_code(204);
                return;
            }
            if ($metodo !== 'POST') {
                Http::json(['ok' => false, 'error' => 'Método no permitido.'], 405);
                return;
            }
            $cuerpo = json_decode((string) file_get_contents('php://input', false, null, 0, 8192), true);
            if (!is_array($cuerpo)) {
                Http::json(['ok' => false, 'error' => 'Solicitud inválida.'], 400);
                return;
            }
            $limite = (int) Config::get('seguridad.limite_por_minuto', 20);
            if (!RateLimiter::permitir('chat', Http::huellaVisitante(), $limite, 60)) {
                Http::json(['ok' => false, 'error' => 'Demasiadas consultas. Espera un minuto e inténtalo de nuevo.'], 429);
                return;
            }
            $accion = (string) ($cuerpo['accion'] ?? 'buscar');
            $contexto = isset($cuerpo['contexto']) && is_array($cuerpo['contexto']) ? $cuerpo['contexto'] : [];
            switch ($accion) {
                case 'buscar':
                    self::buscar((string) ($cuerpo['consulta'] ?? ''), $contexto);
                    return;
                case 'ver':
                    self::ver((int) ($cuerpo['id'] ?? 0), $contexto);
                    return;
                case 'util':
                    self::util((int) ($cuerpo['consulta_id'] ?? 0), (int) ($cuerpo['util'] ?? 0));
                    return;
                case 'inicio':
                    self::inicio();
                    return;
            }
            Http::json(['ok' => false, 'error' => 'Acción desconocida.'], 400);
        } catch (Throwable $e) {
            error_log('[buscador] ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
            Http::json(['ok' => false, 'error' => 'El servicio no está disponible por el momento.'], 500);
        }
    }

    private static function motor(): Buscador
    {
        return new Buscador(new Indice(Repositorio::faqsActivas()));
    }

    /** @param array<string,mixed> $contexto */
    private static function buscar(string $consulta, array $contexto): void
    {
        $consulta = trim((string) preg_replace('/\s+/u', ' ', strip_tags($consulta)));
        $max = (int) Config::get('seguridad.max_longitud', 300);
        if ($consulta === '' || mb_strlen($consulta) > $max) {
            Http::json(['ok' => false, 'error' => 'Escribe una pregunta de hasta ' . $max . ' caracteres.'], 422);
            return;
        }
        $r = self::motor()->buscar($consulta, $contexto);
        unset($r['diagnostico']);
        $r['consulta_id'] = self::registrar($consulta, $r);
        Http::json(['ok' => true] + $r);
    }

    /** @param array<string,mixed> $contexto */
    private static function ver(int $id, array $contexto): void
    {
        $r = self::motor()->ver($id, $contexto);
        if ($r === null) {
            Http::json(['ok' => false, 'error' => 'Pregunta no encontrada.'], 404);
            return;
        }
        // Se registra como consulta elegida para poder votar "¿te sirvió?" y medir las más vistas.
        $r['ms'] = 0;
        $r['consulta_id'] = self::registrar((string) $r['respuesta']['pregunta'], $r);
        Http::json(['ok' => true] + $r);
    }

    private static function util(int $consultaId, int $util): void
    {
        if ($consultaId > 0) {
            Db::ejecutar('UPDATE bf_consulta SET util = ? WHERE id = ? AND util IS NULL', [$util ? 1 : 0, $consultaId]);
        }
        Http::json(['ok' => true]);
    }

    private static function inicio(): void
    {
        $filas = Db::todos(
            "SELECT f.id, f.pregunta, COUNT(c.id) AS n FROM bf_faq f
             LEFT JOIN bf_consulta c ON c.faq_id = f.id AND c.creado_en > (NOW() - INTERVAL 30 DAY)
             WHERE f.estado <> 'archivada' GROUP BY f.id, f.pregunta ORDER BY n DESC, f.id ASC LIMIT 4"
        );
        Http::json(['ok' => true, 'sugerencias' => array_map(static function ($f) {
            return ['id' => (int) $f['id'], 'pregunta' => $f['pregunta']];
        }, $filas)]);
    }

    /** Guarda la consulta sin datos personales (se enmascaran correos y teléfonos). */
    private static function registrar(string $consulta, array $r): ?int
    {
        if (!Config::get('privacidad.registrar_consultas', true)) {
            return null;
        }
        $limpia = (string) preg_replace('/[\w.+-]+@[\w-]+\.[\w.]+/u', '[correo]', $consulta);
        $limpia = (string) preg_replace('/\+?\d[\d\s-]{7,}\d/', '[número]', $limpia);
        Db::ejecutar(
            'INSERT INTO bf_consulta (consulta, resultado, faq_id, confianza, ms) VALUES (?, ?, ?, ?, ?)',
            [mb_substr($limpia, 0, 300), $r['resultado'], $r['faq_id'], $r['confianza'], min(65535, (int) $r['ms'])]
        );
        return Db::ultimoId();
    }
}
