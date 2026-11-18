<?php
declare(strict_types=1);

namespace Buscador\Admin;

use Buscador\Support\Http;

/** Plantilla y ayudantes de HTML del panel. Todo texto variable pasa por Http::e(). */
final class Vista
{
    /** Enlace interno del panel. @param array<string,scalar|null> $params */
    public static function url(string $ruta, array $params = []): string
    {
        $q = ['r' => $ruta] + array_filter($params, static function ($v) {
            return $v !== null && $v !== '';
        });
        return 'index.php?' . http_build_query($q);
    }

    public static function redirigir(string $ruta, array $params = []): void
    {
        header('Location: ' . self::url($ruta, $params), true, 303);
    }

    public static function aviso(string $tipo, string $texto): void
    {
        $_SESSION['aviso'] = [$tipo, $texto];
    }

    public static function campoCsrf(): string
    {
        return '<input type="hidden" name="csrf" value="' . Http::e(Auth::csrf()) . '">';
    }

    /** Formulario POST de un solo botón (archivar, desactivar…). @param array<string,scalar> $ocultos */
    public static function botonPost(string $ruta, string $texto, array $ocultos = [], string $clase = 'btn-sec'): string
    {
        $h = '<form method="post" action="' . Http::e(self::url($ruta)) . '" class="en-linea">' . self::campoCsrf();
        foreach ($ocultos as $k => $v) {
            $h .= '<input type="hidden" name="' . Http::e((string) $k) . '" value="' . Http::e((string) $v) . '">';
        }
        return $h . '<button type="submit" class="' . Http::e($clase) . '">' . Http::e($texto) . '</button></form>';
    }

    /** @param array<string,string> $opciones valor => etiqueta */
    public static function opciones(array $opciones, string $actual, bool $conVacio = false, string $etiquetaVacio = '—'): string
    {
        $h = $conVacio ? '<option value="">' . Http::e($etiquetaVacio) . '</option>' : '';
        foreach ($opciones as $valor => $etiqueta) {
            $sel = (string) $valor === $actual ? ' selected' : '';
            $h .= '<option value="' . Http::e((string) $valor) . '"' . $sel . '>' . Http::e($etiqueta) . '</option>';
        }
        return $h;
    }

    /** Mensaje de error de un campo, enlazado con aria-describedby. @param array<string,string> $errores */
    public static function error(array $errores, string $campo): string
    {
        if (!isset($errores[$campo])) {
            return '';
        }
        return '<p class="error-campo" id="err-' . Http::e($campo) . '">' . Http::e($errores[$campo]) . '</p>';
    }

    /** Atributos de accesibilidad para un campo con error. @param array<string,string> $errores */
    public static function aria(array $errores, string $campo): string
    {
        return isset($errores[$campo]) ? ' aria-invalid="true" aria-describedby="err-' . Http::e($campo) . '"' : '';
    }

    public static function paginacion(string $ruta, array $params, int $pagina, int $total, int $porPagina): string
    {
        $paginas = (int) max(1, ceil($total / $porPagina));
        if ($paginas <= 1) {
            return '';
        }
        $h = '<nav class="paginacion" aria-label="Paginación">';
        if ($pagina > 1) {
            $h .= '<a href="' . Http::e(self::url($ruta, ['p' => $pagina - 1] + $params)) . '">&larr; Anterior</a>';
        }
        $h .= '<span>Página ' . $pagina . ' de ' . $paginas . '</span>';
        if ($pagina < $paginas) {
            $h .= '<a href="' . Http::e(self::url($ruta, ['p' => $pagina + 1] + $params)) . '">Siguiente &rarr;</a>';
        }
        return $h . '</nav>';
    }

    public static function cabeceras(): void
    {
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store, max-age=0');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: same-origin');
        header("Content-Security-Policy: default-src 'none'; style-src 'self'; img-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
        header('X-Robots-Tag: noindex, nofollow');
    }

    /** Imprime la página completa. $cuerpo ya debe venir escapado. */
    public static function pagina(string $titulo, string $cuerpo, string $activo = ''): void
    {
        self::cabeceras();
        try {
            $u = Auth::usuario();
        } catch (\Throwable $e) {
            $u = null; // p. ej. la base no responde: se muestra la página sin menú
        }
        $aviso = '';
        if (!empty($_SESSION['aviso']) && is_array($_SESSION['aviso'])) {
            list($tipo, $texto) = $_SESSION['aviso'];
            unset($_SESSION['aviso']);
            $aviso = '<div class="aviso aviso-' . Http::e((string) $tipo) . '" role="status">' . Http::e((string) $texto) . '</div>';
        }
        $menu = '';
        if ($u !== null) {
            $items = [
                'tablero' => 'Tablero', 'preguntas' => 'Preguntas', 'probador' => 'Probador',
                'sinrespuesta' => 'Sin respuesta', 'areas' => 'Áreas', 'exportar' => 'Exportar CSV',
            ];
            if ($u['rol'] === 'superadmin') {
                $items['cuentas'] = 'Cuentas';
            }
            $items['micuenta'] = 'Mi cuenta';
            $menu .= '<nav class="menu" aria-label="Secciones"><ul>';
            foreach ($items as $r => $etq) {
                $actual = $r === $activo ? ' aria-current="page"' : '';
                $menu .= '<li><a href="' . Http::e(self::url($r)) . '"' . $actual . '>' . Http::e($etq) . '</a></li>';
            }
            $menu .= '<li>' . self::botonPost('salir', 'Salir', [], 'btn-enlace') . '</li></ul></nav>';
        }
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<meta name="robots" content="noindex, nofollow">'
            . '<title>' . Http::e($titulo) . ' · Panel del buscador</title>'
            . '<link rel="stylesheet" href="admin.css"></head><body>'
            . '<a class="saltar" href="#contenido">Saltar al contenido</a>'
            . '<header class="barra"><span class="marca">Panel del buscador</span>'
            . ($u !== null ? '<span class="quien">' . Http::e((string) $u['nombre']) . ' · ' . Http::e(Auth::ROLES[$u['rol']] ?? (string) $u['rol']) . '</span>' : '')
            . '</header>' . $menu
            . '<main id="contenido"><h1>' . Http::e($titulo) . '</h1>' . $aviso . $cuerpo . '</main>'
            . '</body></html>';
    }

    public static function error403(): void
    {
        http_response_code(403);
        self::pagina('Acceso denegado', '<p>Tu cuenta no tiene permiso para esta sección.</p>');
    }
}
