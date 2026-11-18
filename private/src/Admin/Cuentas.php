<?php
declare(strict_types=1);

namespace Buscador\Admin;

use Buscador\Support\Db;

/** Reglas y acceso a datos de las cuentas del panel (lo usan el panel y private/bin/crear-admin.php). */
final class Cuentas
{
    public const MIN_PASSWORD = 12;

    /** Devuelve un mensaje de error o null si la contraseña es aceptable. */
    public static function validarPassword(string $password, string $confirmacion): ?string
    {
        if ($password !== $confirmacion) {
            return 'Las contraseñas no coinciden.';
        }
        if (mb_strlen($password) < self::MIN_PASSWORD) {
            return 'La contraseña debe tener al menos ' . self::MIN_PASSWORD . ' caracteres.';
        }
        if (strlen($password) > 72) {
            return 'La contraseña no puede pasar de 72 bytes (límite de bcrypt).';
        }
        if (trim($password) === '' || preg_match('/^(.)\1+$/u', $password)) {
            return 'Elige una contraseña menos predecible.';
        }
        return null;
    }

    public static function validarUsuario(string $usuario): ?string
    {
        if (!preg_match('/^[a-zA-Z0-9._-]{3,60}$/', $usuario)) {
            return 'El usuario debe tener de 3 a 60 caracteres: letras, números, punto, guion o guion bajo.';
        }
        return null;
    }

    public static function existe(string $usuario): bool
    {
        return Db::uno('SELECT id FROM bf_admin WHERE usuario = ?', [$usuario]) !== null;
    }

    public static function crear(string $usuario, string $nombre, string $rol, string $password): int
    {
        Db::ejecutar(
            'INSERT INTO bf_admin (usuario, nombre, password_hash, rol, activo) VALUES (?, ?, ?, ?, 1)',
            [$usuario, $nombre, password_hash($password, PASSWORD_DEFAULT), $rol]
        );
        return Db::ultimoId();
    }

    public static function cambiarPassword(int $id, string $password): void
    {
        Db::ejecutar('UPDATE bf_admin SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    /** Superadministradores activos distintos de $exceptoId (para no dejar el panel sin ninguno). */
    public static function otrosSuperadminActivos(int $exceptoId): int
    {
        $f = Db::uno("SELECT COUNT(*) AS n FROM bf_admin WHERE rol = 'superadmin' AND activo = 1 AND id <> ?", [$exceptoId]);
        return (int) ($f['n'] ?? 0);
    }
}
