<?php
declare(strict_types=1);

namespace Buscador\Admin\Pantalla;

use Buscador\Admin\Auth;
use Buscador\Admin\Vista;
use Buscador\Support\Http;

final class Acceso
{
    public static function entrar(bool $post): void
    {
        if (Auth::usuario() !== null) {
            Vista::redirigir('tablero');
            return;
        }
        $error = null;
        $usuario = '';
        if ($post) {
            $usuario = is_string($_POST['usuario'] ?? null) ? mb_substr($_POST['usuario'], 0, 60) : '';
            $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
            $error = Auth::entrar($usuario, $password);
            if ($error === null) {
                Vista::redirigir('tablero');
                return;
            }
            http_response_code(strpos($error, 'Demasiados') === 0 ? 429 : 401);
        }
        $h = '';
        if ($error !== null) {
            $h .= '<div class="aviso aviso-error" role="alert">' . Http::e($error) . '</div>';
        }
        $h .= '<form method="post" action="' . Http::e(Vista::url('entrar')) . '" class="acceso-form">' . Vista::campoCsrf()
            . '<div class="campo"><label for="usuario">Usuario</label>'
            . '<input id="usuario" name="usuario" autocomplete="username" required maxlength="60" value="' . Http::e($usuario) . '" autofocus></div>'
            . '<div class="campo"><label for="password">Contraseña</label>'
            . '<div class="campo-clave"><input id="password" name="password" type="password" autocomplete="current-password" required>'
            . '<button type="button" class="ver-clave" data-ver-clave="password" aria-controls="password" aria-pressed="false" aria-label="Mostrar contraseña" title="Mostrar contraseña" hidden>'
            . '<svg class="ojo-abierto" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg><svg class="ojo-cerrado" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.6 5.1A10.6 10.6 0 0 1 12 5c6.4 0 10 7 10 7a17.6 17.6 0 0 1-3.2 4.1M6.6 6.6C3.9 8.4 2 12 2 12s3.6 7 10 7a9.9 9.9 0 0 0 5.4-1.6"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/><path d="M3 3l18 18"/></svg></button></div></div>'
            . '<button type="submit" class="acceso-boton">Entrar al panel</button></form>'
            . '<p class="acceso-nota">¿Necesitas una cuenta o olvidaste tu contraseña? Pídesela a un superadministrador del panel: '
            . 'puede crear cuentas y restablecer contraseñas en la sección <strong>Cuentas</strong>.</p>';
        Vista::paginaAcceso($h);
    }

    public static function salir(bool $post): void
    {
        Auth::salir();
        Auth::iniciarSesion();
        Vista::aviso('info', 'Sesión cerrada.');
        Vista::redirigir('entrar');
    }
}
