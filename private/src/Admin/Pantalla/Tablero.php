<?php
declare(strict_types=1);

namespace Buscador\Admin\Pantalla;

use Buscador\Admin\Faqs;
use Buscador\Admin\Vista;
use Buscador\Support\Db;
use Buscador\Support\Http;

final class Tablero
{
    private const RESULTADOS = [
        'respondida'    => 'Respondidas',
        'sugerencias'   => 'Con sugerencias',
        'sin_respuesta' => 'Sin respuesta',
        'no_ofertada'   => 'Carrera no ofertada',
    ];

    public static function ver(bool $post): void
    {
        $estados = array_fill_keys(array_keys(Faqs::ESTADOS), 0);
        foreach (Db::todos('SELECT estado, COUNT(*) AS n FROM bf_faq GROUP BY estado') as $f) {
            $estados[$f['estado']] = (int) $f['n'];
        }
        $resultados = array_fill_keys(array_keys(self::RESULTADOS), 0);
        foreach (Db::todos('SELECT resultado, COUNT(*) AS n FROM bf_consulta WHERE creado_en >= (NOW() - INTERVAL 30 DAY) GROUP BY resultado') as $f) {
            $resultados[$f['resultado']] = (int) $f['n'];
        }
        $totalConsultas = array_sum($resultados);
        $m = Db::uno(
            'SELECT SUM(util = 1) AS positivos, SUM(util IS NOT NULL) AS votos, AVG(ms) AS ms
             FROM bf_consulta WHERE creado_en >= (NOW() - INTERVAL 30 DAY)'
        );
        $votos = (int) ($m['votos'] ?? 0);
        $positivos = (int) ($m['positivos'] ?? 0);
        $top = Db::todos(
            'SELECT f.id, f.pregunta, COUNT(*) AS n FROM bf_consulta c JOIN bf_faq f ON f.id = c.faq_id
             WHERE c.creado_en >= (NOW() - INTERVAL 30 DAY)
             GROUP BY f.id, f.pregunta ORDER BY n DESC, f.id ASC LIMIT 10'
        );

        $h = '<section class="tarjetas" aria-label="Indicadores de los últimos 30 días">'
            . self::tarjeta('Consultas (30 días)', number_format($totalConsultas))
            . self::tarjeta('¿Te sirvió? positivo', $votos > 0 ? round(100 * $positivos / $votos) . ' %' : 'sin votos', $votos > 0 ? $positivos . ' de ' . $votos . ' votos' : '')
            . self::tarjeta('Tiempo medio de respuesta', $m['ms'] !== null ? round((float) $m['ms']) . ' ms' : '—')
            . self::tarjeta('Preguntas validadas', $estados['validada'] . ' de ' . array_sum($estados))
            . '</section>';

        $h .= '<div class="dos-columnas"><section><h2>Preguntas por estado</h2><table><thead><tr><th scope="col">Estado</th><th scope="col" class="num">Preguntas</th></tr></thead><tbody>';
        foreach (Faqs::ESTADOS as $clave => $nombre) {
            $h .= '<tr><td><a href="' . Http::e(Vista::url('preguntas', ['estado' => $clave])) . '">' . Http::e($nombre) . '</a></td><td class="num">' . $estados[$clave] . '</td></tr>';
        }
        $h .= '</tbody></table></section>';

        $h .= '<section><h2>Consultas por resultado (30 días)</h2><table><thead><tr><th scope="col">Resultado</th><th scope="col" class="num">Consultas</th><th scope="col">Proporción</th></tr></thead><tbody>';
        foreach (self::RESULTADOS as $clave => $nombre) {
            $n = $resultados[$clave];
            $pct = $totalConsultas > 0 ? round(100 * $n / $totalConsultas) : 0;
            $h .= '<tr><td>' . Http::e($nombre) . '</td><td class="num">' . $n . '</td><td><meter min="0" max="100" value="' . $pct . '" aria-label="' . Http::e($nombre) . '">' . $pct . ' %</meter> ' . $pct . ' %</td></tr>';
        }
        $h .= '</tbody></table></section></div>';

        $h .= '<section><h2>Las 10 preguntas más consultadas (30 días)</h2>';
        if (!$top) {
            $h .= '<p>Aún no hay consultas respondidas en este periodo.</p>';
        } else {
            $h .= '<ol class="top">';
            foreach ($top as $t) {
                $h .= '<li><a href="' . Http::e(Vista::url('pregunta', ['id' => (int) $t['id']])) . '">' . Http::e((string) $t['pregunta']) . '</a> <span class="cuenta">' . (int) $t['n'] . '</span></li>';
            }
            $h .= '</ol>';
        }
        $h .= '</section>';
        Vista::pagina('Tablero', $h, 'tablero');
    }

    private static function tarjeta(string $titulo, string $valor, string $nota = ''): string
    {
        return '<div class="tarjeta"><p class="t-titulo">' . Http::e($titulo) . '</p><p class="t-valor">' . Http::e($valor) . '</p>'
            . ($nota !== '' ? '<p class="t-nota">' . Http::e($nota) . '</p>' : '') . '</div>';
    }
}
