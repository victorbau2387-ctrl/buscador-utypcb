<?php
declare(strict_types=1);

namespace Buscador\Admin\Pantalla;

use Buscador\Admin\Auth;
use Buscador\Admin\Cuentas;
use Buscador\Admin\Vista;
use Buscador\Support\Db;
use Buscador\Support\Http;

/** Gestión de cuentas (solo superadmin) y "Mi cuenta" (cualquier usuario). Las contraseñas nunca se muestran. */
final class CuentasPantalla
{
    public static function lista(bool $post): void
    {
        $yo = (int) Auth::usuario()['id'];
        $filas = Db::todos('SELECT id, usuario, nombre, rol, activo, ultimo_acceso FROM bf_admin ORDER BY activo DESC, usuario');
        $h = '<div class="cabeza-seccion"><p class="nota">Crea cuentas para las personas de cada área que mantendrán las respuestas. '
            . '<strong>Editor</strong>: preguntas, áreas y revisión. <strong>Superadministrador</strong>: además gestiona cuentas.</p>'
            . '<a class="boton boton-sol" href="' . Http::e(Vista::url('cuenta_nueva')) . '">+ Crear cuenta</a></div>'
            . '<div class="tabla-scroll"><table><thead><tr><th scope="col">Usuario</th><th scope="col">Nombre</th><th scope="col">Rol</th><th scope="col">Estado</th><th scope="col">Último acceso</th><th scope="col">Acciones</th></tr></thead><tbody>';
        foreach ($filas as $c) {
            $id = (int) $c['id'];
            $activo = (int) $c['activo'] === 1;
            $acciones = '';
            if ($id !== $yo) {
                $otroRol = $c['rol'] === 'superadmin' ? 'editor' : 'superadmin';
                $acciones .= Vista::botonPost('cuenta_accion', 'Hacer ' . (Auth::ROLES[$otroRol]), ['id' => $id, 'accion' => 'rol', 'rol' => $otroRol]) . ' '
                    . Vista::botonPost('cuenta_accion', $activo ? 'Desactivar' : 'Reactivar', ['id' => $id, 'accion' => $activo ? 'desactivar' : 'activar']) . ' '
                    . '<a class="boton btn-sec" href="' . Http::e(Vista::url('cuenta_password', ['id' => $id])) . '">Restablecer contraseña</a>';
            } else {
                $acciones = '<a href="' . Http::e(Vista::url('micuenta')) . '">Mi cuenta</a>';
            }
            $h .= '<tr><td>' . Http::e((string) $c['usuario']) . '</td><td>' . Http::e((string) $c['nombre']) . '</td>'
                . '<td>' . Http::e(Auth::ROLES[$c['rol']] ?? (string) $c['rol']) . '</td>'
                . '<td>' . ($activo ? 'Activa' : 'Desactivada') . '</td>'
                . '<td>' . Http::e((string) ($c['ultimo_acceso'] ?? 'nunca')) . '</td><td class="acciones">' . $acciones . '</td></tr>';
        }
        $h .= '</tbody></table></div>';
        Vista::pagina('Cuentas', $h, 'cuentas');
    }

    public static function nueva(bool $post): void
    {
        $datos = ['usuario' => '', 'nombre' => '', 'rol' => 'editor'];
        $err = [];
        if ($post) {
            foreach ($datos as $k => $_) {
                $datos[$k] = is_string($_POST[$k] ?? null) ? trim(mb_substr($_POST[$k], 0, 150)) : '';
            }
            $p1 = self::post('password');
            $p2 = self::post('password2');
            if (($e = Cuentas::validarUsuario($datos['usuario'])) !== null) {
                $err['usuario'] = $e;
            } elseif (Cuentas::existe($datos['usuario'])) {
                $err['usuario'] = 'Ese usuario ya existe.';
            }
            if ($datos['nombre'] === '' || mb_strlen($datos['nombre']) > 120) {
                $err['nombre'] = 'Nombre obligatorio (máx. 120 caracteres).';
            }
            if (!isset(Auth::ROLES[$datos['rol']])) {
                $err['rol'] = 'Rol inválido.';
            }
            if (($e = Cuentas::validarPassword($p1, $p2)) !== null) {
                $err['password'] = $e;
            }
            if (!$err) {
                Cuentas::crear($datos['usuario'], $datos['nombre'], $datos['rol'], $p1);
                Vista::aviso('ok', 'Cuenta creada. Comunica la contraseña a la persona por un medio seguro y pídele que la cambie en "Mi cuenta".');
                Vista::redirigir('cuentas');
                return;
            }
        }
        $h = ($err ? '<div class="aviso aviso-error" role="alert">Revisa los campos marcados.</div>' : '')
            . '<form method="post" action="' . Http::e(Vista::url('cuenta_nueva')) . '" class="formulario angosto" autocomplete="off">' . Vista::campoCsrf()
            . '<div class="campo"><label for="n-usuario">Usuario</label><input id="n-usuario" name="usuario" required maxlength="60" value="' . Http::e($datos['usuario']) . '"' . Vista::aria($err, 'usuario') . '>' . Vista::error($err, 'usuario') . '</div>'
            . '<div class="campo"><label for="n-nombre">Nombre completo</label><input id="n-nombre" name="nombre" required maxlength="120" value="' . Http::e($datos['nombre']) . '"' . Vista::aria($err, 'nombre') . '>' . Vista::error($err, 'nombre') . '</div>'
            . '<div class="campo"><label for="n-rol">Rol</label><select id="n-rol" name="rol">' . Vista::opciones(Auth::ROLES, $datos['rol']) . '</select>' . Vista::error($err, 'rol')
            . '<p class="ayuda">Editor: preguntas, áreas y revisión. Superadministrador: además gestiona cuentas.</p></div>'
            . self::camposPassword($err, 'Contraseña inicial')
            . '<div class="acciones"><button type="submit">Crear cuenta</button> <a href="' . Http::e(Vista::url('cuentas')) . '">Cancelar</a></div></form>';
        Vista::pagina('Nueva cuenta', $h, 'cuentas');
    }

    public static function accion(bool $post): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $accion = (string) ($_POST['accion'] ?? '');
        $yo = (int) Auth::usuario()['id'];
        $c = Db::uno('SELECT id, rol, activo FROM bf_admin WHERE id = ?', [$id]);
        if ($c === null || $id === $yo) {
            Vista::aviso('error', 'No puedes modificar tu propia cuenta desde aquí ni una cuenta inexistente.');
            Vista::redirigir('cuentas');
            return;
        }
        $quitaSuper = $c['rol'] === 'superadmin' && (int) $c['activo'] === 1
            && ($accion === 'desactivar' || ($accion === 'rol' && ($_POST['rol'] ?? '') !== 'superadmin'));
        if ($quitaSuper && Cuentas::otrosSuperadminActivos($id) === 0) {
            Vista::aviso('error', 'Debe quedar al menos un superadministrador activo.');
            Vista::redirigir('cuentas');
            return;
        }
        if ($accion === 'desactivar' || $accion === 'activar') {
            Db::ejecutar('UPDATE bf_admin SET activo = ? WHERE id = ?', [$accion === 'activar' ? 1 : 0, $id]);
            Vista::aviso('ok', $accion === 'activar' ? 'Cuenta reactivada.' : 'Cuenta desactivada. Su sesión se cierra en la siguiente acción.');
        } elseif ($accion === 'rol' && isset(Auth::ROLES[(string) ($_POST['rol'] ?? '')])) {
            Db::ejecutar('UPDATE bf_admin SET rol = ? WHERE id = ?', [(string) $_POST['rol'], $id]);
            Vista::aviso('ok', 'Rol actualizado.');
        } else {
            Vista::aviso('error', 'Acción no válida.');
        }
        Vista::redirigir('cuentas');
    }

    public static function restablecer(bool $post): void
    {
        $id = (int) ($post ? ($_POST['id'] ?? 0) : ($_GET['id'] ?? 0));
        $c = Db::uno('SELECT id, usuario, nombre FROM bf_admin WHERE id = ?', [$id]);
        if ($c === null || $id === (int) Auth::usuario()['id']) {
            Vista::aviso('error', 'Para cambiar tu propia contraseña usa "Mi cuenta".');
            Vista::redirigir('cuentas');
            return;
        }
        $err = [];
        if ($post) {
            if (($e = Cuentas::validarPassword(self::post('password'), self::post('password2'))) !== null) {
                $err['password'] = $e;
            } else {
                Cuentas::cambiarPassword($id, self::post('password'));
                Vista::aviso('ok', 'Contraseña restablecida. Comunícala por un medio seguro y pide que la cambien en "Mi cuenta".');
                Vista::redirigir('cuentas');
                return;
            }
        }
        $h = ($err ? '<div class="aviso aviso-error" role="alert">Revisa los campos marcados.</div>' : '')
            . '<p>Cuenta: <strong>' . Http::e((string) $c['usuario']) . '</strong> (' . Http::e((string) $c['nombre']) . ')</p>'
            . '<form method="post" action="' . Http::e(Vista::url('cuenta_password')) . '" class="formulario angosto" autocomplete="off">' . Vista::campoCsrf()
            . '<input type="hidden" name="id" value="' . $id . '">'
            . self::camposPassword($err, 'Nueva contraseña')
            . '<div class="acciones"><button type="submit">Restablecer</button> <a href="' . Http::e(Vista::url('cuentas')) . '">Cancelar</a></div></form>';
        Vista::pagina('Restablecer contraseña', $h, 'cuentas');
    }

    public static function miCuenta(bool $post): void
    {
        $u = Auth::usuario();
        $err = [];
        if ($post) {
            $fila = Db::uno('SELECT password_hash FROM bf_admin WHERE id = ?', [(int) $u['id']]);
            if ($fila === null || !password_verify(self::post('actual'), (string) $fila['password_hash'])) {
                $err['actual'] = 'La contraseña actual no es correcta.';
            } elseif (($e = Cuentas::validarPassword(self::post('password'), self::post('password2'))) !== null) {
                $err['password'] = $e;
            } elseif (hash_equals(self::post('actual'), self::post('password'))) {
                $err['password'] = 'La nueva contraseña debe ser distinta de la actual.';
            }
            if (!$err) {
                Cuentas::cambiarPassword((int) $u['id'], self::post('password'));
                Auth::renovar();
                Vista::aviso('ok', 'Tu contraseña se cambió.');
                Vista::redirigir('micuenta');
                return;
            }
        }
        $h = '<dl class="ficha"><dt>Usuario</dt><dd>' . Http::e((string) $u['usuario']) . '</dd><dt>Nombre</dt><dd>' . Http::e((string) $u['nombre']) . '</dd>'
            . '<dt>Rol</dt><dd>' . Http::e(Auth::ROLES[$u['rol']] ?? (string) $u['rol']) . '</dd></dl>'
            . '<h2>Cambiar mi contraseña</h2>'
            . ($err ? '<div class="aviso aviso-error" role="alert">Revisa los campos marcados.</div>' : '')
            . '<form method="post" action="' . Http::e(Vista::url('micuenta')) . '" class="formulario angosto">' . Vista::campoCsrf()
            . '<div class="campo"><label for="m-actual">Contraseña actual</label><input id="m-actual" name="actual" type="password" required autocomplete="current-password"' . Vista::aria($err, 'actual') . '>' . Vista::error($err, 'actual') . '</div>'
            . self::camposPassword($err, 'Nueva contraseña')
            . '<div class="acciones"><button type="submit">Cambiar contraseña</button></div></form>';
        Vista::pagina('Mi cuenta', $h, 'micuenta');
    }

    /** @param array<string,string> $err */
    private static function camposPassword(array $err, string $etiqueta): string
    {
        return '<div class="campo"><label for="pw1">' . Http::e($etiqueta) . '</label>'
            . '<input id="pw1" name="password" type="password" required minlength="' . Cuentas::MIN_PASSWORD . '" autocomplete="new-password"' . Vista::aria($err, 'password') . '>'
            . '<p class="ayuda">Mínimo ' . Cuentas::MIN_PASSWORD . ' caracteres. Una frase larga es más segura y fácil de recordar.</p>' . Vista::error($err, 'password') . '</div>'
            . '<div class="campo"><label for="pw2">Repite la contraseña</label>'
            . '<input id="pw2" name="password2" type="password" required minlength="' . Cuentas::MIN_PASSWORD . '" autocomplete="new-password"></div>';
    }

    private static function post(string $k): string
    {
        return is_string($_POST[$k] ?? null) ? (string) $_POST[$k] : '';
    }
}
