<?php
declare(strict_types=1);

namespace Buscador\Admin\Pantalla;

use Buscador\Admin\Areas as ReglasArea;
use Buscador\Admin\Vista;
use Buscador\Support\Db;
use Buscador\Support\Http;

final class Areas
{
    public static function lista(bool $post): void
    {
        $filas = Db::todos(
            'SELECT a.*, (SELECT COUNT(*) FROM bf_faq f WHERE f.area_id = a.id) AS npreg FROM bf_area a ORDER BY a.nombre'
        );
        $h = '<p><a class="boton" href="' . Http::e(Vista::url('area')) . '">Nueva área</a></p>';
        if (!$filas) {
            $h .= '<p>Aún no hay áreas registradas.</p>';
        } else {
            $h .= '<div class="tabla-scroll"><table><thead><tr><th scope="col">Área</th><th scope="col">Clave</th><th scope="col">Contacto</th><th scope="col" class="num">Preguntas</th></tr></thead><tbody>';
            foreach ($filas as $a) {
                $contacto = array_filter([(string) $a['correo'], (string) $a['telefono'], (string) $a['url']], 'strlen');
                $h .= '<tr><td><a href="' . Http::e(Vista::url('area', ['id' => (int) $a['id']])) . '">' . Http::e((string) $a['nombre']) . '</a></td>'
                    . '<td>' . Http::e((string) $a['clave']) . '</td><td>' . Http::e(implode(' · ', $contacto)) . '</td>'
                    . '<td class="num">' . (int) $a['npreg'] . '</td></tr>';
            }
            $h .= '</tbody></table></div>';
        }
        Vista::pagina('Áreas responsables', $h, 'areas');
    }

    public static function formulario(bool $post): void
    {
        $id = (int) ($post ? ($_POST['id'] ?? 0) : ($_GET['id'] ?? 0));
        $a = ['clave' => '', 'nombre' => '', 'correo' => '', 'telefono' => '', 'url' => ''];
        if ($id > 0) {
            $fila = Db::uno('SELECT * FROM bf_area WHERE id = ?', [$id]);
            if ($fila === null) {
                http_response_code(404);
                Vista::pagina('No encontrada', '<p>El área no existe.</p>', 'areas');
                return;
            }
            foreach ($a as $k => $_) {
                $a[$k] = (string) ($fila[$k] ?? '');
            }
        }
        $err = [];
        if ($post) {
            foreach ($a as $k => $_) {
                $a[$k] = is_string($_POST[$k] ?? null) ? trim(mb_substr($_POST[$k], 0, 300)) : '';
            }
            $a['clave'] = mb_strtolower($a['clave']);
            $err = ReglasArea::validar($a);
            if (!isset($err['clave']) && Db::uno('SELECT id FROM bf_area WHERE clave = ? AND id <> ?', [$a['clave'], $id]) !== null) {
                $err['clave'] = 'Ya existe otra área con esa clave.';
            }
            if (!$err) {
                ReglasArea::escribir($a, $id);
                Vista::aviso('ok', 'Área guardada.');
                Vista::redirigir('areas');
                return;
            }
        }
        $campo = static function (string $k, string $etq, string $extra = '', string $ayuda = '') use ($a, $err): string {
            return '<div class="campo"><label for="a-' . $k . '">' . Http::e($etq) . '</label>'
                . '<input id="a-' . $k . '" name="' . $k . '" value="' . Http::e($a[$k]) . '"' . $extra . Vista::aria($err, $k) . '>'
                . ($ayuda !== '' ? '<p class="ayuda">' . Http::e($ayuda) . '</p>' : '') . Vista::error($err, $k) . '</div>';
        };
        $h = ($err ? '<div class="aviso aviso-error" role="alert">Revisa los campos marcados.</div>' : '')
            . '<form method="post" action="' . Http::e(Vista::url('area')) . '" class="formulario">' . Vista::campoCsrf()
            . '<input type="hidden" name="id" value="' . $id . '">'
            . $campo('nombre', 'Nombre', ' required maxlength="120"')
            . $campo('clave', 'Clave', ' required maxlength="40" pattern="[a-z0-9_-]+"', 'Corta, en minúsculas; es la que usa la columna "area" del CSV. Ej.: escolares.')
            . $campo('correo', 'Correo oficial', ' type="email" maxlength="120"')
            . $campo('telefono', 'Teléfono', ' maxlength="40"')
            . $campo('url', 'Página (https://)', ' type="url" maxlength="255"')
            . '<div class="acciones"><button type="submit">Guardar</button> <a href="' . Http::e(Vista::url('areas')) . '">Cancelar</a></div></form>';
        if ($id > 0) {
            $n = (int) (Db::uno('SELECT COUNT(*) AS n FROM bf_faq WHERE area_id = ?', [$id])['n'] ?? 0);
            $h .= '<section class="zona-sec"><h2>Eliminar área</h2><p>'
                . ($n > 0 ? 'Hay ' . $n . ' pregunta(s) asignadas a esta área: quedarán sin área.' : 'No hay preguntas asignadas.') . '</p>'
                . Vista::botonPost('area_borrar', 'Eliminar esta área', ['id' => $id]) . '</section>';
        }
        Vista::pagina($id > 0 ? 'Editar área' : 'Nueva área', $h, 'areas');
    }

    public static function borrar(bool $post): void
    {
        Db::ejecutar('DELETE FROM bf_area WHERE id = ?', [(int) ($_POST['id'] ?? 0)]);
        Vista::aviso('ok', 'Área eliminada.');
        Vista::redirigir('areas');
    }
}
