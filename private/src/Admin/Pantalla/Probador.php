<?php
declare(strict_types=1);

namespace Buscador\Admin\Pantalla;

use Buscador\Admin\Vista;
use Buscador\Search\Buscador;
use Buscador\Search\Indice;
use Buscador\Search\Repositorio;
use Buscador\Support\Config;
use Buscador\Support\Http;

/** Ejecuta el motor con una consulta y muestra por qué responde lo que responde. No registra en bf_consulta. */
final class Probador
{
    public static function ver(bool $post): void
    {
        $q = isset($_GET['q']) && is_string($_GET['q']) ? trim(mb_substr($_GET['q'], 0, (int) Config::get('seguridad.max_longitud', 300))) : '';
        $carrera = isset($_GET['carrera']) && is_string($_GET['carrera']) ? trim(mb_substr($_GET['carrera'], 0, 80)) : '';

        $h = '<form method="get" action="index.php" class="filtros" role="search"><input type="hidden" name="r" value="probador">'
            . '<div class="ancho"><label for="p-q">Consulta</label><input id="p-q" name="q" type="search" value="' . Http::e($q) . '" required maxlength="300"></div>'
            . '<div><label for="p-carrera">Carrera en contexto (opcional)</label><input id="p-carrera" name="carrera" value="' . Http::e($carrera) . '" maxlength="80"></div>'
            . '<div><button type="submit">Probar</button></div></form>'
            . '<p class="nota">Usa las preguntas ' . (Config::get('busqueda.solo_validadas', false) ? 'validadas' : 'no archivadas')
            . ' de la base, igual que el buscador público. Las pruebas no se registran en las estadísticas.</p>';

        if ($q !== '') {
            $indice = new Indice(Repositorio::faqsActivas());
            $r = (new Buscador($indice))->buscar($q, ['carrera' => $carrera !== '' ? $carrera : null]);
            $d = isset($r['diagnostico']) && is_array($r['diagnostico']) ? $r['diagnostico'] : [];
            $terminos = isset($d['terminos']) && is_array($d['terminos']) ? array_map('strval', $d['terminos']) : [];

            $h .= '<section><h2>Resultado</h2><dl class="ficha">'
                . '<dt>Resultado</dt><dd><span class="estado res-' . Http::e((string) $r['resultado']) . '">' . Http::e((string) $r['resultado']) . '</span></dd>'
                . '<dt>Confianza</dt><dd>' . Http::e(number_format((float) $r['confianza'], 3)) . ' <small>(responde directo desde ' . Http::e((string) Config::get('busqueda.umbral_respuesta', 0.62)) . ', sugiere desde ' . Http::e((string) Config::get('busqueda.umbral_sugerencias', 0.30)) . ')</small></dd>'
                . '<dt>Términos</dt><dd>' . ($terminos ? Http::e(implode(' · ', $terminos)) : '<em>ninguno reconocido</em>') . '</dd>'
                . '<dt>Carrera detectada</dt><dd>' . Http::e((string) ($d['carrera'] ?? '—')) . (!empty($d['uso_contexto']) ? ' (tomada del contexto)' : '') . '</dd>'
                . '<dt>Tiempo</dt><dd>' . (int) $r['ms'] . ' ms</dd></dl>';

            if (!empty($r['respuesta'])) {
                $resp = $r['respuesta'];
                $h .= '<h3>Respuesta</h3><div class="respuesta"><p><strong>' . Http::e((string) $resp['pregunta']) . '</strong>'
                    . (!empty($resp['id']) ? ' — <a href="' . Http::e(Vista::url('pregunta', ['id' => (int) $resp['id']])) . '">editar</a>' : '') . '</p>'
                    . '<p class="pre">' . Http::e((string) $resp['texto']) . '</p>'
                    . (!empty($resp['fuente']) ? '<p><small>Fuente: ' . Http::e((string) $resp['fuente']) . '</small></p>' : '') . '</div>';
            }
            if (!empty($r['sugerencias'])) {
                $h .= '<h3>Sugerencias</h3><ul>';
                foreach ($r['sugerencias'] as $s) {
                    $h .= '<li>' . Http::e((string) ($s['pregunta'] ?? '')) . ' <small>(' . Http::e(number_format((float) ($s['confianza'] ?? 0), 3)) . ')</small>'
                        . (!empty($s['id']) ? ' — <a href="' . Http::e(Vista::url('pregunta', ['id' => (int) $s['id']])) . '">editar</a>' : '') . '</li>';
                }
                $h .= '</ul>';
            }
            if (!empty($r['canalizar'])) {
                $h .= '<h3>Canaliza a</h3><p>' . Http::e((string) ($r['canalizar']['nombre'] ?? '')) . '</p>';
            }

            // Puntajes crudos del índice (antes del ajuste por carrera) para ver la competencia entre preguntas.
            if ($terminos) {
                $puntajes = array_slice($indice->calificar($terminos), 0, 8, true);
                if ($puntajes) {
                    $h .= '<h3>Preguntas con más puntaje</h3><div class="tabla-scroll"><table><thead><tr><th scope="col">Pregunta</th><th scope="col" class="num">Puntaje</th></tr></thead><tbody>';
                    foreach ($puntajes as $id => $p) {
                        $faq = $indice->faq((int) $id);
                        $h .= '<tr><td><a href="' . Http::e(Vista::url('pregunta', ['id' => (int) $id])) . '">' . Http::e((string) ($faq['pregunta'] ?? ('#' . $id))) . '</a></td><td class="num">' . Http::e(number_format((float) $p, 3)) . '</td></tr>';
                    }
                    $h .= '</tbody></table></div>';
                }
            }
            $h .= '<details><summary>Diagnóstico completo (JSON)</summary><pre>'
                . Http::e((string) json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
                . '</pre></details></section>';
        }
        Vista::pagina('Probador', $h, 'probador');
    }
}
