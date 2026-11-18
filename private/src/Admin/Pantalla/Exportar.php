<?php
declare(strict_types=1);

namespace Buscador\Admin\Pantalla;

use Buscador\Admin\Exportador;
use Buscador\Admin\Vista;
use Buscador\Support\Db;
use Buscador\Support\Http;

final class Exportar
{
    public static function ver(bool $post): void
    {
        $n = (int) (Db::uno('SELECT COUNT(*) AS n FROM bf_faq')['n'] ?? 0);
        $h = '<p>Descarga la base de conocimiento completa en CSV (UTF-8, compatible con Excel) con el mismo formato que '
            . '<code>conocimiento/preguntas.csv</code>: una fila por pregunta, variantes separadas por "|" y el área por su clave.</p>'
            . '<p>Sirve para que las áreas revisen y validen las respuestas, y se puede volver a importar con <code>private/bin/importar.php</code>.</p>'
            . '<p class="acciones"><a class="boton" href="' . Http::e(Vista::url('exportar_csv')) . '">Descargar todas (' . $n . ')</a> '
            . '<a class="boton btn-sec" href="' . Http::e(Vista::url('exportar_csv', ['activas' => 1])) . '">Descargar sin archivadas</a></p>'
            . '<p class="nota">Las celdas que empiezan con =, +, - o @ se exportan con un apóstrofo inicial para que Excel no las ejecute como fórmula; el importador lo quita.</p>';
        Vista::pagina('Exportar base de conocimiento', $h, 'exportar');
    }

    public static function descargar(bool $post): void
    {
        $activas = !empty($_GET['activas']);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="preguntas-' . date('Y-m-d') . '.csv"');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        $fh = fopen('php://output', 'wb');
        if ($fh === false) {
            throw new \RuntimeException('No se pudo abrir la salida.');
        }
        Exportador::escribir($fh, !$activas);
        fclose($fh);
    }
}
