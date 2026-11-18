<?php
/**
 * API SIMULADA para probar el widget sin MariaDB (solo desarrollo local).
 *
 * Responde con el MISMO contrato que public_html/buscador/api.php y usa el motor real
 * (Buscador + Indice) con las preguntas de conocimiento/preguntas.csv, sin base de datos.
 * No registra consultas ni aplica límite por minuto.
 *
 * Forzar errores para probar el manejo en el widget (parámetro en la URL del endpoint):
 *   ?forzar=429    → "Demasiadas consultas"
 *   ?forzar=500    → error del servidor
 *   ?forzar=lento  → tarda 10 s (el widget corta a los 8 s)
 *   ?forzar=basura → responde algo que no es JSON
 */
declare(strict_types=1);

require __DIR__ . '/../../private/src/bootstrap.php';

use Buscador\Search\Buscador;
use Buscador\Search\Csv;
use Buscador\Search\Indice;
use Buscador\Support\Config;

// BF_CONFIG puede venir relativo a la raíz del repositorio; el servidor embebido ejecuta este
// archivo con cwd = tests/sitio-local, así que se resuelve aquí y se recarga la configuración.
$bfConfig = (string) getenv('BF_CONFIG');
$candidatos = [$bfConfig, dirname(__DIR__, 2) . '/' . $bfConfig, __DIR__ . '/../config.pruebas.php'];
foreach ($candidatos as $c) {
    if ($c !== '' && is_file($c)) {
        Config::cargar($c);
        break;
    }
}

function bf_sim_json(array $datos, int $estado = 200): void
{
    http_response_code($estado);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

$forzar = isset($_GET['forzar']) ? (string) $_GET['forzar'] : '';
if ($forzar === '429') {
    bf_sim_json(['ok' => false, 'error' => 'Demasiadas consultas. Espera un minuto e inténtalo de nuevo.'], 429);
    return;
}
if ($forzar === '500') {
    bf_sim_json(['ok' => false, 'error' => 'El servicio no está disponible por el momento.'], 500);
    return;
}
if ($forzar === 'lento') {
    sleep(10);
}
if ($forzar === 'basura') {
    header('Content-Type: text/html; charset=utf-8');
    echo '<b>Fatal error</b>';
    return;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    bf_sim_json(['ok' => false, 'error' => 'Método no permitido.'], 405);
    return;
}
$cuerpo = json_decode((string) file_get_contents('php://input', false, null, 0, 8192), true);
if (!is_array($cuerpo)) {
    bf_sim_json(['ok' => false, 'error' => 'Solicitud inválida.'], 400);
    return;
}

$raiz = dirname(__DIR__, 2);
$faqs = Csv::faqsEnMemoria($raiz . '/conocimiento/preguntas.csv', $raiz . '/conocimiento/areas.csv');
$motor = new Buscador(new Indice($faqs));
$contexto = isset($cuerpo['contexto']) && is_array($cuerpo['contexto']) ? $cuerpo['contexto'] : [];

switch ((string) ($cuerpo['accion'] ?? 'buscar')) {
    case 'inicio':
        $sug = [];
        foreach (array_slice($faqs, 0, 4) as $f) {
            $sug[] = ['id' => (int) $f['id'], 'pregunta' => (string) $f['pregunta']];
        }
        bf_sim_json(['ok' => true, 'sugerencias' => $sug]);
        return;

    case 'buscar':
        $consulta = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) ($cuerpo['consulta'] ?? ''))));
        if ($consulta === '' || mb_strlen($consulta) > 300) {
            bf_sim_json(['ok' => false, 'error' => 'Escribe una pregunta de hasta 300 caracteres.'], 422);
            return;
        }
        $r = $motor->buscar($consulta, $contexto);
        unset($r['diagnostico']);
        $r['consulta_id'] = random_int(1, 999999);
        bf_sim_json(['ok' => true] + $r);
        return;

    case 'ver':
        $r = $motor->ver((int) ($cuerpo['id'] ?? 0), $contexto);
        if ($r === null) {
            bf_sim_json(['ok' => false, 'error' => 'Pregunta no encontrada.'], 404);
            return;
        }
        bf_sim_json(['ok' => true] + $r);
        return;

    case 'util':
        bf_sim_json(['ok' => true]);
        return;
}
bf_sim_json(['ok' => false, 'error' => 'Acción desconocida.'], 400);
