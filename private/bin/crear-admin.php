<?php
// Crea una cuenta del panel (o restablece la contraseña de una existente).
//
//   php private/bin/crear-admin.php
//
// Pide usuario, nombre, rol y la contraseña dos veces SIN mostrarla en pantalla.
// No hay contraseñas predefinidas: la primera cuenta superadmin se crea así, desde SSH o la
// terminal del panel de hosting. En Windows usa PowerShell para ocultar la contraseña.
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}
require __DIR__ . '/../src/bootstrap.php';

use Buscador\Admin\Auth;
use Buscador\Admin\Cuentas;

function bf_preguntar(string $texto): string
{
    fwrite(STDOUT, $texto);
    $linea = fgets(STDIN);
    if ($linea === false) {
        fwrite(STDERR, "\nEntrada terminada.\n");
        exit(1);
    }
    return trim($linea);
}

function bf_funcion_disponible(string $f): bool
{
    $deshabilitadas = array_map('trim', explode(',', (string) ini_get('disable_functions')));
    return function_exists($f) && !in_array($f, $deshabilitadas, true);
}

/** Lee una contraseña sin eco. Si no se puede ocultar, avisa y la lee visible. */
function bf_leer_oculto(string $texto): string
{
    $esTerminal = function_exists('stream_isatty') ? @stream_isatty(STDIN) : true;
    if (!$esTerminal) {
        // Entrada redirigida (p. ej. desde un archivo o tubería): no hay eco que ocultar.
        fwrite(STDOUT, $texto . "\n");
        return rtrim((string) fgets(STDIN), "\r\n");
    }
    if (bf_funcion_disponible('shell_exec')) {
        if (DIRECTORY_SEPARATOR === '\\') {
            fwrite(STDOUT, $texto);
            $ps = '$s = Read-Host -AsSecureString; [Console]::OutputEncoding = [Text.Encoding]::UTF8; '
                . '$b = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($s); '
                . '[Console]::Out.Write([Runtime.InteropServices.Marshal]::PtrToStringBSTR($b)); '
                . '[Runtime.InteropServices.Marshal]::ZeroFreeBSTR($b)';
            $r = shell_exec('powershell -NoProfile -Command "' . $ps . '"');
            if (is_string($r)) {
                return rtrim($r, "\r\n");
            }
        } else {
            $estado = trim((string) shell_exec('stty -g 2>/dev/null'));
            if ($estado !== '') {
                fwrite(STDOUT, $texto);
                shell_exec('stty -echo 2>/dev/null');
                $linea = fgets(STDIN);
                shell_exec('stty ' . escapeshellarg($estado) . ' 2>/dev/null');
                fwrite(STDOUT, "\n");
                return rtrim((string) $linea, "\r\n");
            }
        }
    }
    fwrite(STDERR, "AVISO: no se puede ocultar lo que escribes en esta terminal; la contraseña se verá en pantalla.\n");
    fwrite(STDOUT, $texto);
    return rtrim((string) fgets(STDIN), "\r\n");
}

try {
    fwrite(STDOUT, "Crear cuenta del panel del buscador\n\n");
    $usuario = bf_preguntar('Usuario: ');
    if (($e = Cuentas::validarUsuario($usuario)) !== null) {
        fwrite(STDERR, $e . "\n");
        exit(1);
    }
    $restablecer = false;
    $nombre = '';
    $rol = 'editor';
    if (Cuentas::existe($usuario)) {
        $r = strtolower(bf_preguntar('Ese usuario ya existe. ¿Restablecer su contraseña? (s/N): '));
        if ($r !== 's' && $r !== 'si' && $r !== 'sí') {
            fwrite(STDOUT, "Sin cambios.\n");
            exit(0);
        }
        $restablecer = true;
    } else {
        $nombre = bf_preguntar('Nombre completo: ');
        if ($nombre === '' || mb_strlen($nombre) > 120) {
            fwrite(STDERR, "El nombre es obligatorio (máx. 120 caracteres).\n");
            exit(1);
        }
        $rol = strtolower(bf_preguntar('Rol [superadmin/editor] (editor): '));
        $rol = $rol === '' ? 'editor' : $rol;
        if (!isset(Auth::ROLES[$rol])) {
            fwrite(STDERR, "Rol inválido. Usa superadmin o editor.\n");
            exit(1);
        }
    }
    $p1 = bf_leer_oculto('Contraseña (mínimo ' . Cuentas::MIN_PASSWORD . ' caracteres): ');
    $p2 = bf_leer_oculto('Repite la contraseña: ');
    if (($e = Cuentas::validarPassword($p1, $p2)) !== null) {
        fwrite(STDERR, $e . "\n");
        exit(1);
    }
    if ($restablecer) {
        $fila = \Buscador\Support\Db::uno('SELECT id FROM bf_admin WHERE usuario = ?', [$usuario]);
        Cuentas::cambiarPassword((int) $fila['id'], $p1);
        fwrite(STDOUT, "Contraseña restablecida para \"$usuario\".\n");
    } else {
        Cuentas::crear($usuario, $nombre, $rol, $p1);
        fwrite(STDOUT, "Cuenta \"$usuario\" creada con rol $rol.\n");
    }
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}
