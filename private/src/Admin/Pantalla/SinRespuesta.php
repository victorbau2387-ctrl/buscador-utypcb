<?php
declare(strict_types=1);

namespace Buscador\Admin\Pantalla;

use Buscador\Admin\Faqs;
use Buscador\Admin\Vista;
use Buscador\Support\Db;
use Buscador\Support\Http;

/** Consultas que el buscador no resolvió bien: sin respuesta, solo sugerencias o marcadas "no me sirvió". */
final class SinRespuesta
{
    private const POR_PAGINA = 30;
    private const FILTROS = [
        ''              => 'Todas las pendientes',
        'sin_respuesta' => 'Sin respuesta',
        'sugerencias'   => 'Solo sugerencias',
        'no_sirvio'     => 'Marcadas "no me sirvió"',
    ];

    public static function lista(bool $post): void
    {
        $tipo = isset($_GET['tipo']) && is_string($_GET['tipo']) && isset(self::FILTROS[$_GET['tipo']]) ? $_GET['tipo'] : '';
        $pagina = max(1, (int) ($_GET['p'] ?? 1));
        $cond = [
            ''              => "(c.resultado IN ('sin_respuesta','sugerencias') OR c.util = 0)",
            'sin_respuesta' => "c.resultado = 'sin_respuesta'",
            'sugerencias'   => "c.resultado = 'sugerencias'",
            'no_sirvio'     => 'c.util = 0',
        ][$tipo];
        $where = 'WHERE c.revisada = 0 AND ' . $cond;
        $total = (int) (Db::uno('SELECT COUNT(*) AS n FROM bf_consulta c ' . $where)['n'] ?? 0);
        $filas = Db::todos(
            'SELECT c.id, c.creado_en, c.consulta, c.resultado, c.confianza, c.util, f.pregunta
             FROM bf_consulta c LEFT JOIN bf_faq f ON f.id = c.faq_id ' . $where . '
             ORDER BY c.creado_en DESC LIMIT ' . self::POR_PAGINA . ' OFFSET ' . (int) (($pagina - 1) * self::POR_PAGINA)
        );

        $h = '<form method="get" action="index.php" class="filtros"><input type="hidden" name="r" value="sinrespuesta">'
            . '<div><label for="s-tipo">Mostrar</label><select id="s-tipo" name="tipo">' . Vista::opciones(self::FILTROS, $tipo) . '</select></div>'
            . '<div><button type="submit">Filtrar</button></div></form>'
            . '<p class="nota">' . $total . ' consulta(s) pendientes de revisar. Los correos y teléfonos aparecen enmascarados.</p>';
        if (!$filas) {
            $h .= '<p>No hay consultas pendientes con este filtro.</p>';
        } else {
            $h .= '<ul class="consultas">';
            foreach ($filas as $c) {
                $id = (int) $c['id'];
                $etq = $c['resultado'] === 'sin_respuesta' ? 'Sin respuesta' : ($c['resultado'] === 'sugerencias' ? 'Sugerencias' : (string) $c['resultado']);
                $h .= '<li><p class="consulta">“' . Http::e((string) $c['consulta']) . '”</p>'
                    . '<p class="meta"><span class="estado res-' . Http::e((string) $c['resultado']) . '">' . Http::e($etq) . '</span> '
                    . ($c['util'] !== null && (int) $c['util'] === 0 ? '<span class="estado res-sin_respuesta">No le sirvió</span> ' : '')
                    . Http::e((string) $c['creado_en']) . ' · confianza ' . Http::e(number_format((float) $c['confianza'], 2))
                    . ($c['pregunta'] !== null ? ' · respondió: ' . Http::e((string) $c['pregunta']) : '') . '</p>'
                    . '<p class="acciones">'
                    . '<a class="boton" href="' . Http::e(Vista::url('pregunta', ['desde' => $id])) . '">Crear pregunta a partir de esta</a> '
                    . '<a class="boton btn-sec" href="' . Http::e(Vista::url('sr_variante', ['id' => $id])) . '">Agregar como variante de…</a> '
                    . '<a class="boton btn-sec" href="' . Http::e(Vista::url('probador', ['q' => (string) $c['consulta']])) . '">Probar</a> '
                    . Vista::botonPost('sr_revisada', 'Marcar revisada', ['id' => $id, 'tipo' => $tipo])
                    . '</p></li>';
            }
            $h .= '</ul>';
        }
        $h .= Vista::paginacion('sinrespuesta', ['tipo' => $tipo], $pagina, $total, self::POR_PAGINA);
        Vista::pagina('Consultas sin respuesta', $h, 'sinrespuesta');
    }

    public static function variante(bool $post): void
    {
        $id = (int) ($post ? ($_POST['id'] ?? 0) : ($_GET['id'] ?? 0));
        $c = Db::uno('SELECT id, consulta FROM bf_consulta WHERE id = ?', [$id]);
        if ($c === null) {
            http_response_code(404);
            Vista::pagina('No encontrada', '<p>La consulta ya no existe (quizá se purgó).</p>', 'sinrespuesta');
            return;
        }
        $error = '';
        if ($post) {
            $faqId = (int) ($_POST['faq_id'] ?? 0);
            $texto = is_string($_POST['texto'] ?? null) ? (string) $_POST['texto'] : (string) $c['consulta'];
            if (Db::uno("SELECT id FROM bf_faq WHERE id = ? AND estado <> 'archivada'", [$faqId]) === null) {
                $error = 'Elige una pregunta de la lista.';
            } elseif (trim($texto) === '') {
                $error = 'La variante no puede quedar vacía.';
            } else {
                $agregada = Faqs::agregarVariante($faqId, $texto);
                Db::ejecutar('UPDATE bf_consulta SET revisada = 1 WHERE id = ?', [$id]);
                Vista::aviso('ok', $agregada ? 'Variante agregada y consulta marcada como revisada.' : 'Esa variante ya existía; la consulta se marcó como revisada.');
                Vista::redirigir('sinrespuesta');
                return;
            }
        }
        $opciones = '<option value="">— elige una pregunta —</option>';
        $grupo = null;
        foreach (Faqs::paraSelector() as $f) {
            if ($f['categoria'] !== $grupo) {
                $opciones .= ($grupo !== null ? '</optgroup>' : '') . '<optgroup label="' . Http::e((string) $f['categoria']) . '">';
                $grupo = $f['categoria'];
            }
            $opciones .= '<option value="' . (int) $f['id'] . '">' . Http::e((string) $f['pregunta']) . '</option>';
        }
        $opciones .= $grupo !== null ? '</optgroup>' : '';
        $h = ($error !== '' ? '<div class="aviso aviso-error" role="alert">' . Http::e($error) . '</div>' : '')
            . '<form method="post" action="' . Http::e(Vista::url('sr_variante')) . '" class="formulario">' . Vista::campoCsrf()
            . '<input type="hidden" name="id" value="' . $id . '">'
            . '<div class="campo"><label for="v-texto">Variante (puedes corregirla antes de agregarla)</label>'
            . '<input id="v-texto" name="texto" maxlength="255" required value="' . Http::e((string) $c['consulta']) . '"></div>'
            . '<div class="campo"><label for="v-faq">Agregarla a la pregunta</label><select id="v-faq" name="faq_id" required>' . $opciones . '</select></div>'
            . '<div class="acciones"><button type="submit">Agregar variante</button> <a href="' . Http::e(Vista::url('sinrespuesta')) . '">Cancelar</a></div></form>';
        Vista::pagina('Agregar como variante', $h, 'sinrespuesta');
    }

    public static function revisada(bool $post): void
    {
        Db::ejecutar('UPDATE bf_consulta SET revisada = 1 WHERE id = ?', [(int) ($_POST['id'] ?? 0)]);
        Vista::aviso('ok', 'Consulta marcada como revisada.');
        $tipo = is_string($_POST['tipo'] ?? null) && isset(self::FILTROS[$_POST['tipo']]) ? $_POST['tipo'] : '';
        Vista::redirigir('sinrespuesta', ['tipo' => $tipo]);
    }
}
