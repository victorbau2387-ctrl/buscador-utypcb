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
            http_response_code(401);
        }
        $h = '';
        if ($error !== null) {
            $h .= '<div class="aviso aviso-error" role="alert">' . Http::e($error) . '</div>';
        }
        $h .= '<form method="post" action="' . Http::e(Vista::url('entrar')) . '" class="formulario angosto">' . Vista::campoCsrf()
            . '<label for="usuario">Usuario</label>'
            . '<input id="usuario" name="usuario" autocomplete="username" required maxlength="60" value="' . Http::e($usuario) . '" autofocus>'
            . '<label for="password">Contraseña</label>'
            . '<input id="password" name="password" type="password" autocomplete="current-password" required>'
            . '<button type="submit">Entrar</button></form>'
            . '<p class="nota">Las cuentas las crea el responsable técnico. Si olvidaste tu contraseña, pídele que la restablezca.</p>';
        Vista::pagina('Entrar', $h);
    }

    public static function salir(bool $post): void
    {
        Auth::salir();
        Auth::iniciarSesion();
        Vista::aviso('info', 'Sesión cerrada.');
        Vista::redirigir('entrar');
    }
}
