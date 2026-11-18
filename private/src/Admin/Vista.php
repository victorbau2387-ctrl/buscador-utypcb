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
        header("Content-Security-Policy: default-src 'none'; script-src 'self'; connect-src 'self'; style-src 'self' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
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
            . self::recursos() . '</head><body>'
            . '<a class="saltar" href="#contenido">Saltar al contenido</a>'
            . '<header class="barra"><span class="marca"><span class="marca-utyp">UTyP</span> Panel del buscador</span>'
            . ($u !== null ? '<span class="quien">' . Http::e((string) $u['nombre']) . ' · ' . Http::e(Auth::ROLES[$u['rol']] ?? (string) $u['rol']) . '</span>' : '')
            . '</header>' . $menu
            . '<main id="contenido"><h1>' . Http::e($titulo) . '</h1>' . $aviso . $cuerpo . '</main>'
            . self::widget() . '</body></html>';
    }

    /** Hojas de estilo y script del panel, con las tipografías de utypcb.edu.mx. */
    private static function recursos(): string
    {
        $v = static function ($f) {
            $r = dirname((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) . '/' . $f;
            return is_file($r) ? (string) filemtime($r) : '1';
        };
        return '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
            . '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500..700&family=Archivo:wght@400..700&family=JetBrains+Mono:wght@400;600&display=swap">'
            . '<link rel="stylesheet" href="admin.css?v=' . $v('admin.css') . '">'
            . '<script src="admin.js?v=' . $v('admin.js') . '" defer></script>';
    }

    /**
     * El mismo botón "¿Tienes dudas?" del sitio, en la esquina del panel, para probar el buscador sin salir.
     * Sin el enlace "Acceso para personal" (ya se está en el panel).
     */
    private static function widget(): string
    {
        $archivo = dirname((string) ($_SERVER['SCRIPT_FILENAME'] ?? ''), 2) . '/widget.php';
        if (!is_file($archivo)) {
            return '';
        }
        if (!defined('BF_WIDGET_BASE')) {
            define('BF_WIDGET_BASE', '../');
        }
        if (!defined('BF_WIDGET_API')) {
            define('BF_WIDGET_API', '../api.php');
        }
        if (!defined('BF_WIDGET_ADMIN')) {
            define('BF_WIDGET_ADMIN', false);
        }
        ob_start();
        require $archivo;
        return (string) ob_get_clean();
    }

    /** Pantalla de acceso a dos columnas: marca de la UTyP a la izquierda, tarjeta de acceso a la derecha. */
    public static function paginaAcceso(string $cuerpo): void
    {
        self::cabeceras();
        $aviso = '';
        if (!empty($_SESSION['aviso']) && is_array($_SESSION['aviso'])) {
            list($tipo, $texto) = $_SESSION['aviso'];
            unset($_SESSION['aviso']);
            $aviso = '<div class="aviso aviso-' . Http::e((string) $tipo) . '" role="status">' . Http::e((string) $texto) . '</div>';
        }
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<meta name="robots" content="noindex, nofollow">'
            . '<title>Entrar · Panel del buscador · UTyP Coyuca de Benítez</title>'
            . self::recursos() . '</head><body class="acceso">'
            . '<main class="acceso-marco" id="contenido">'
            . '<section class="acceso-marca" aria-labelledby="acceso-titulo">'
            . '<p class="acceso-sup"><span class="marca-utyp">UTyP</span> Coyuca de Benítez · Guerrero</p>'
            . '<h1 id="acceso-titulo">Panel del buscador <em>de preguntas frecuentes</em></h1>'
            . '<p class="acceso-lema">Aquí se mantienen las respuestas que ven aspirantes y alumnos en el sitio de la universidad.</p>'
            . '<ul class="acceso-puntos">'
            . '<li>Edita preguntas, respuestas y sus variantes</li>'
            . '<li>Revisa lo que el buscador no pudo responder</li>'
            . '<li>Valida cada respuesta con el área responsable</li>'
            . '</ul>'
            . '<p class="acceso-pie">Universidad Tecnológica · Costa de Guerrero</p>'
            . '</section>'
            . '<section class="acceso-tarjeta" aria-labelledby="acceso-entrar">'
            . '<h2 id="acceso-entrar">Inicia sesión</h2>'
            . '<p class="acceso-sub">Usa la cuenta que te asignó un superadministrador.</p>'
            . $aviso . $cuerpo
            . '</section>'
            . '</main>'
            . self::widget() . '</body></html>';
    }

    public static function error403(): void
    {
        http_response_code(403);
        self::pagina('Acceso denegado', '<p>Tu cuenta no tiene permiso para esta sección.</p>');
    }
}
