<?php
declare(strict_types=1);

namespace Buscador\Admin\Pantalla;

use Buscador\Admin\Faqs;
use Buscador\Admin\Vista;
use Buscador\Search\Dinamicas;
use Buscador\Site\DatosSitio;
use Buscador\Support\Db;
use Buscador\Support\Http;

final class Preguntas
{
    private const POR_PAGINA = 25;

    public static function lista(bool $post): void
    {
        $cat = self::get('categoria');
        $estado = self::get('estado');
        $q = trim(self::get('q'));
        $pagina = max(1, (int) self::get('p'));

        $where = [];
        $params = [];
        if ($cat !== '') {
            $where[] = 'f.categoria = ?';
            $params[] = $cat;
        }
        if ($estado === '') {
            $where[] = "f.estado <> 'archivada'";
        } elseif (isset(Faqs::ESTADOS[$estado])) {
            $where[] = 'f.estado = ?';
            $params[] = $estado;
        }
        if ($q !== '') {
            $like = '%' . addcslashes(mb_substr($q, 0, 100), '%_\\') . '%';
            $where[] = '(f.pregunta LIKE ? OR f.respuesta LIKE ? OR f.slug LIKE ? OR f.palabras_clave LIKE ? OR EXISTS (SELECT 1 FROM bf_variante v WHERE v.faq_id = f.id AND v.texto LIKE ?))';
            array_push($params, $like, $like, $like, $like, $like);
        }
        $sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $total = (int) (Db::uno('SELECT COUNT(*) AS n FROM bf_faq f ' . $sqlWhere, $params)['n'] ?? 0);
        $offset = ($pagina - 1) * self::POR_PAGINA;
        $filas = Db::todos(
            'SELECT f.id, f.slug, f.categoria, f.pregunta, f.tipo, f.estado, a.nombre AS area,
                    (SELECT COUNT(*) FROM bf_variante v WHERE v.faq_id = f.id) AS nvar
             FROM bf_faq f LEFT JOIN bf_area a ON a.id = f.area_id ' . $sqlWhere . '
             ORDER BY f.categoria, f.pregunta LIMIT ' . self::POR_PAGINA . ' OFFSET ' . (int) $offset,
            $params
        );

        $cats = [];
        foreach (Faqs::categorias() as $c) {
            $cats[$c] = $c;
        }
        $estadosFiltro = ['' => 'Activas (sin archivadas)'] + Faqs::ESTADOS + ['todas' => 'Todas'];
        $h = '<p><a class="boton" href="' . Http::e(Vista::url('pregunta')) . '">Nueva pregunta</a></p>'
            . '<form method="get" action="index.php" class="filtros" role="search"><input type="hidden" name="r" value="preguntas">'
            . '<div><label for="f-q">Texto</label><input id="f-q" name="q" type="search" value="' . Http::e($q) . '" maxlength="100"></div>'
            . '<div><label for="f-cat">Categoría</label><select id="f-cat" name="categoria">' . Vista::opciones($cats, $cat, true, 'Todas') . '</select></div>'
            . '<div><label for="f-estado">Estado</label><select id="f-estado" name="estado">' . Vista::opciones($estadosFiltro, $estado) . '</select></div>'
            . '<div><button type="submit">Filtrar</button></div></form>'
            . '<p class="nota">' . $total . ' pregunta(s).</p>';
        if ($filas) {
            $h .= '<div class="tabla-scroll"><table><thead><tr><th scope="col">Pregunta</th><th scope="col">Categoría</th><th scope="col">Tipo</th><th scope="col">Estado</th><th scope="col">Área</th><th scope="col" class="num">Variantes</th></tr></thead><tbody>';
            foreach ($filas as $f) {
                $h .= '<tr><td><a href="' . Http::e(Vista::url('pregunta', ['id' => (int) $f['id']])) . '">' . Http::e((string) $f['pregunta']) . '</a><br><small>' . Http::e((string) $f['slug']) . '</small></td>'
                    . '<td>' . Http::e((string) $f['categoria']) . '</td><td>' . Http::e((string) $f['tipo']) . '</td>'
                    . '<td><span class="estado estado-' . Http::e((string) $f['estado']) . '">' . Http::e(Faqs::ESTADOS[$f['estado']] ?? (string) $f['estado']) . '</span></td>'
                    . '<td>' . Http::e((string) ($f['area'] ?? '—')) . '</td><td class="num">' . (int) $f['nvar'] . '</td></tr>';
            }
            $h .= '</tbody></table></div>';
        }
        $h .= Vista::paginacion('preguntas', ['categoria' => $cat, 'estado' => $estado, 'q' => $q], $pagina, $total, self::POR_PAGINA);
        Vista::pagina('Preguntas', $h, 'preguntas');
    }

    public static function formulario(bool $post): void
    {
        $id = (int) ($post ? ($_POST['id'] ?? 0) : self::get('id'));
        $consultaId = (int) ($post ? ($_POST['consulta_id'] ?? 0) : self::get('desde'));
        $errores = [];
        if ($id > 0) {
            $actual = Faqs::obtener($id);
            if ($actual === null) {
                http_response_code(404);
                Vista::pagina('No encontrada', '<p>La pregunta no existe.</p>', 'preguntas');
                return;
            }
        } else {
            $actual = Faqs::vacia();
        }

        if ($post) {
            $f = self::desdeFormulario();
            $areaExiste = $f['area_id'] === null || Db::uno('SELECT id FROM bf_area WHERE id = ?', [$f['area_id']]) !== null;
            $errores = Faqs::validar($f, $areaExiste);
            if (!isset($errores['slug']) && Faqs::slugEnUso((string) $f['slug'], $id)) {
                $errores['slug'] = 'Ya existe otra pregunta con ese identificador.';
            }
            if (!$errores) {
                $nuevoId = Faqs::guardar($f, $id);
                if ($consultaId > 0) {
                    Db::ejecutar('UPDATE bf_consulta SET revisada = 1 WHERE id = ?', [$consultaId]);
                }
                Vista::aviso('ok', $id > 0 ? 'Pregunta actualizada.' : 'Pregunta creada.');
                Vista::redirigir('pregunta', ['id' => $nuevoId]);
                return;
            }
            $f['id'] = $id;
            $actual = $f;
        } elseif ($consultaId > 0 && $id === 0) {
            $c = Db::uno('SELECT consulta FROM bf_consulta WHERE id = ?', [$consultaId]);
            if ($c !== null) {
                $actual['variantes'] = [(string) $c['consulta']];
                $actual['pregunta'] = (string) $c['consulta'];
            }
        }
        $titulo = $id > 0 ? 'Editar pregunta' : 'Nueva pregunta';
        Vista::pagina($titulo, self::html($actual, $errores, $consultaId), 'preguntas');
    }

    public static function archivar(bool $post): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        Db::ejecutar("UPDATE bf_faq SET estado = 'archivada' WHERE id = ?", [$id]);
        Vista::aviso('ok', 'Pregunta archivada. Ya no aparece en el buscador; puedes reactivarla cambiando su estado.');
        Vista::redirigir('pregunta', ['id' => $id]);
    }

    /** @return array<string,mixed> */
    private static function desdeFormulario(): array
    {
        $t = static function (string $k, int $max = 60000): string {
            $v = $_POST[$k] ?? '';
            return is_string($v) ? mb_substr(trim(str_replace("\r\n", "\n", $v)), 0, $max) : '';
        };
        $area = (int) ($_POST['area_id'] ?? 0);
        return [
            'slug'           => mb_strtolower($t('slug', 100)),
            'categoria'      => mb_strtolower($t('categoria', 60)),
            'pregunta'       => $t('pregunta', 300),
            'respuesta'      => $t('respuesta'),
            'tipo'           => $t('tipo', 20),
            'handler'        => $t('tipo', 20) === 'dinamica' ? $t('handler', 60) : '',
            'palabras_clave' => $t('palabras_clave', 600),
            'carrera'        => $t('carrera', 100),
            'fuente_url'     => $t('fuente_url', 300),
            'area_id'        => $area > 0 ? $area : null,
            'estado'         => $t('estado', 20),
            'validado_por'   => $t('validado_por', 150),
            'validado_en'    => $t('validado_en', 20),
            'variantes'      => Faqs::variantesDeTexto($t('variantes')),
        ];
    }

    /** @param array<string,mixed> $f @param array<string,string> $err */
    private static function html(array $f, array $err, int $consultaId): string
    {
        $areas = [];
        foreach (Db::todos('SELECT id, nombre FROM bf_area ORDER BY nombre') as $a) {
            $areas[(string) $a['id']] = (string) $a['nombre'];
        }
        $cats = '';
        foreach (Faqs::categorias() as $c) {
            $cats .= '<option value="' . Http::e($c) . '">';
        }
        $carreras = '';
        try {
            foreach (DatosSitio::carreras() as $c) {
                $carreras .= '<option value="' . Http::e((string) $c['slug']) . '">' . Http::e((string) $c['nombre']) . '</option>';
            }
        } catch (\Throwable $e) {
            $carreras = ''; // sin datos del sitio el campo queda libre
        }
        $handlers = [];
        foreach (Dinamicas::HANDLERS as $k => $desc) {
            $handlers[$k] = $k . ' — ' . $desc;
        }
        $id = (int) $f['id'];
        $campo = static function (string $nombre, string $etiqueta, string $control, string $ayuda = '') use ($err): string {
            return '<div class="campo"><label for="c-' . $nombre . '">' . Http::e($etiqueta) . '</label>' . $control
                . ($ayuda !== '' ? '<p class="ayuda" id="ay-' . $nombre . '">' . Http::e($ayuda) . '</p>' : '')
                . Vista::error($err, $nombre) . '</div>';
        };
        $input = static function (string $nombre, string $valor, string $extra = '') use ($err): string {
            return '<input id="c-' . $nombre . '" name="' . $nombre . '" value="' . Http::e($valor) . '"' . $extra . Vista::aria($err, $nombre) . '>';
        };

        $h = '';
        if ($err) {
            $h .= '<div class="aviso aviso-error" role="alert">Revisa los campos marcados (' . count($err) . ').</div>';
        }
        if ($consultaId > 0) {
            $h .= '<p class="nota">Se creará a partir de una consulta sin respuesta; al guardar, la consulta se marcará como revisada.</p>';
        }
        $h .= '<form method="post" action="' . Http::e(Vista::url('pregunta')) . '" class="formulario">' . Vista::campoCsrf()
            . '<input type="hidden" name="id" value="' . $id . '"><input type="hidden" name="consulta_id" value="' . $consultaId . '">'
            . '<fieldset><legend>Pregunta</legend>'
            . $campo('pregunta', 'Pregunta (como se mostrará)', $input('pregunta', (string) $f['pregunta'], ' required maxlength="255"'))
            . $campo('slug', 'Identificador (slug)', $input('slug', (string) $f['slug'], ' required maxlength="80" pattern="[a-z0-9]+(-[a-z0-9]+)*"'), 'Minúsculas y guiones, único. Ej.: becas-requisitos.')
            . $campo('categoria', 'Categoría', $input('categoria', (string) $f['categoria'], ' required maxlength="40" list="lista-cat"') . '<datalist id="lista-cat">' . $cats . '</datalist>')
            . $campo('variantes', 'Variantes (una por línea)', '<textarea id="c-variantes" name="variantes" rows="7"' . Vista::aria($err, 'variantes') . '>' . Http::e(implode("\n", (array) $f['variantes'])) . '</textarea>', 'Formas en que la gente lo pregunta: coloquial, sin acentos, con faltas. Mínimo ' . Faqs::MIN_VARIANTES_VALIDADA . ' para poder validarla. Hay ' . count((array) $f['variantes']) . '.')
            . $campo('palabras_clave', 'Palabras clave', $input('palabras_clave', (string) $f['palabras_clave'], ' maxlength="500"'), 'Separadas por espacios.')
            . '</fieldset><fieldset><legend>Respuesta</legend>'
            . $campo('tipo', 'Tipo', '<select id="c-tipo" name="tipo"' . Vista::aria($err, 'tipo') . '>' . Vista::opciones(Faqs::TIPOS, (string) $f['tipo']) . '</select>', 'Las dinámicas arman la respuesta en vivo con los datos del sitio (calendario, carreras, noticias…).')
            . $campo('handler', 'Handler (solo dinámicas)', '<select id="c-handler" name="handler"' . Vista::aria($err, 'handler') . '>' . Vista::opciones($handlers, (string) $f['handler'], true, '— ninguno —') . '</select>')
            . $campo('respuesta', 'Respuesta', '<textarea id="c-respuesta" name="respuesta" rows="8"' . Vista::aria($err, 'respuesta') . '>' . Http::e((string) $f['respuesta']) . '</textarea>', 'Obligatoria en las fijas. En las dinámicas es un texto introductorio opcional.')
            . $campo('carrera', 'Carrera (opcional)', '<input id="c-carrera" name="carrera" maxlength="80" list="lista-carreras" value="' . Http::e((string) $f['carrera']) . '"' . Vista::aria($err, 'carrera') . '><datalist id="lista-carreras">' . $carreras . '</datalist>', 'Slug de la carrera si la respuesta es específica de una.')
            . $campo('fuente_url', 'Fuente oficial (URL https://)', $input('fuente_url', (string) $f['fuente_url'], ' type="url" required maxlength="255" pattern="https://.+"'))
            . $campo('area_id', 'Área responsable', '<select id="c-area_id" name="area_id"' . Vista::aria($err, 'area') . '>' . Vista::opciones($areas, (string) ($f['area_id'] ?? ''), true, '— sin área —') . '</select>' . Vista::error($err, 'area'))
            . '</fieldset><fieldset><legend>Validación</legend>'
            . $campo('estado', 'Estado', '<select id="c-estado" name="estado"' . Vista::aria($err, 'estado') . '>' . Vista::opciones(Faqs::ESTADOS, (string) $f['estado']) . '</select>', 'Para "Validada" se exigen quién validó, la fecha y al menos ' . Faqs::MIN_VARIANTES_VALIDADA . ' variantes.')
            . $campo('validado_por', 'Validada por (nombre y área)', $input('validado_por', (string) $f['validado_por'], ' maxlength="120"'))
            . $campo('validado_en', 'Fecha de validación', $input('validado_en', (string) $f['validado_en'], ' type="date"'))
            . '</fieldset><div class="acciones"><button type="submit">Guardar</button> <a href="' . Http::e(Vista::url('preguntas')) . '">Cancelar</a></div></form>';
        if ($id > 0 && $f['estado'] !== 'archivada') {
            $h .= '<section class="zona-sec"><h2>Archivar</h2><p>Archivar la quita del buscador sin borrarla (se conserva el historial).</p>'
                . Vista::botonPost('pregunta_archivar', 'Archivar esta pregunta', ['id' => $id]) . '</section>';
        }
        if ($id > 0) {
            $h .= '<p><a href="' . Http::e(Vista::url('probador', ['q' => (string) $f['pregunta']])) . '">Probar esta pregunta en el probador</a></p>';
        }
        return $h;
    }

    private static function get(string $k): string
    {
        return isset($_GET[$k]) && is_string($_GET[$k]) ? $_GET[$k] : '';
    }
}
