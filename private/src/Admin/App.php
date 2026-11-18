<?php
declare(strict_types=1);

namespace Buscador\Admin;

use Buscador\Admin\Pantalla\Acceso;
use Buscador\Admin\Pantalla\Areas;
use Buscador\Admin\Pantalla\CuentasPantalla;
use Buscador\Admin\Pantalla\Exportar;
use Buscador\Admin\Pantalla\Preguntas;
use Buscador\Admin\Pantalla\Probador;
use Buscador\Admin\Pantalla\SinRespuesta;
use Buscador\Admin\Pantalla\Tablero;
use Buscador\Support\Config;
use Buscador\Support\Http;
use Throwable;

/**
 * Front controller del panel: index.php?r=ruta.
 * Cada ruta indica clase, método, rol mínimo (null = pública) y métodos HTTP aceptados.
 */
final class App
{
    private const PUBLICA = null;
    private const EDITOR = 'editor';
    private const SUPER = 'superadmin';

    /** @return array<string,array{0:class-string,1:string,2:?string,3:string[]}> */
    private static function rutas(): array
    {
        return [
            'entrar'             => [Acceso::class, 'entrar', self::PUBLICA, ['GET', 'POST']],
            'salir'              => [Acceso::class, 'salir', self::PUBLICA, ['POST']],
            'tablero'            => [Tablero::class, 'ver', self::EDITOR, ['GET']],
            'preguntas'          => [Preguntas::class, 'lista', self::EDITOR, ['GET']],
            'pregunta'           => [Preguntas::class, 'formulario', self::EDITOR, ['GET', 'POST']],
            'pregunta_archivar'  => [Preguntas::class, 'archivar', self::EDITOR, ['POST']],
            'probador'           => [Probador::class, 'ver', self::EDITOR, ['GET']],
            'sinrespuesta'       => [SinRespuesta::class, 'lista', self::EDITOR, ['GET']],
            'sr_variante'        => [SinRespuesta::class, 'variante', self::EDITOR, ['GET', 'POST']],
            'sr_revisada'        => [SinRespuesta::class, 'revisada', self::EDITOR, ['POST']],
            'areas'              => [Areas::class, 'lista', self::EDITOR, ['GET']],
            'area'               => [Areas::class, 'formulario', self::EDITOR, ['GET', 'POST']],
            'area_borrar'        => [Areas::class, 'borrar', self::EDITOR, ['POST']],
            'exportar'           => [Exportar::class, 'ver', self::EDITOR, ['GET']],
            'exportar_csv'       => [Exportar::class, 'descargar', self::EDITOR, ['GET']],
            'micuenta'           => [CuentasPantalla::class, 'miCuenta', self::EDITOR, ['GET', 'POST']],
            'cuentas'            => [CuentasPantalla::class, 'lista', self::SUPER, ['GET']],
            'cuenta_nueva'       => [CuentasPantalla::class, 'nueva', self::SUPER, ['GET', 'POST']],
            'cuenta_accion'      => [CuentasPantalla::class, 'accion', self::SUPER, ['POST']],
            'cuenta_password'    => [CuentasPantalla::class, 'restablecer', self::SUPER, ['GET', 'POST']],
        ];
    }

    public static function atender(): void
    {
        try {
            Auth::iniciarSesion();
            $metodo = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
            $ruta = isset($_GET['r']) && is_string($_GET['r']) ? $_GET['r'] : 'tablero';
            $rutas = self::rutas();

            if ($metodo === 'POST' && !Auth::csrfValido()) {
                http_response_code(400);
                Vista::pagina('Solicitud no válida', '<p>El formulario caducó o no es válido. Vuelve a la página anterior, recárgala e inténtalo de nuevo.</p>');
                return;
            }
            if (!isset($rutas[$ruta])) {
                http_response_code(404);
                Vista::pagina('No encontrado', '<p>Esa sección no existe.</p><p><a href="' . Http::e(Vista::url('tablero')) . '">Ir al tablero</a></p>');
                return;
            }
            list($clase, $accion, $rol, $metodos) = $rutas[$ruta];
            if (!in_array($metodo, $metodos, true)) {
                http_response_code(405);
                header('Allow: ' . implode(', ', $metodos));
                Vista::pagina('Método no permitido', '<p>Esta acción no admite ese tipo de solicitud.</p>');
                return;
            }
            if ($rol !== self::PUBLICA) {
                $u = Auth::usuario();
                if ($u === null) {
                    if ($metodo === 'GET') {
                        Vista::redirigir('entrar');
                    } else {
                        http_response_code(403);
                        Vista::pagina('Sesión cerrada', '<p>Tu sesión terminó. <a href="' . Http::e(Vista::url('entrar')) . '">Vuelve a entrar</a>.</p>');
                    }
                    return;
                }
                if ($rol === self::SUPER && $u['rol'] !== 'superadmin') {
                    Vista::error403();
                    return;
                }
            }
            call_user_func([$clase, $accion], $metodo === 'POST');
        } catch (Throwable $e) {
            error_log('[buscador-admin] ' . get_class($e) . ': ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
            if (!headers_sent()) {
                http_response_code(500);
            }
            $detalle = Config::get('entorno') === 'desarrollo'
                ? '<pre>' . Http::e(get_class($e) . ': ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine()) . '</pre>'
                : '';
            Vista::pagina('Error', '<p>Ocurrió un error inesperado. Se registró en la bitácora del servidor.</p>' . $detalle);
        }
    }
}
