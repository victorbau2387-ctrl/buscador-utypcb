<?php
declare(strict_types=1);

namespace Buscador\Admin;

use Buscador\Support\Config;
use Buscador\Support\Db;
use Buscador\Support\Http;
use Buscador\Support\RateLimiter;

/**
 * Sesión del panel: cookie propia (HttpOnly, SameSite=Strict, Secure con HTTPS), cierre por
 * inactividad, token CSRF y límite de intentos de acceso.
 */
final class Auth
{
    public const NOMBRE_SESION = 'bf_panel';
    public const INACTIVIDAD = 1800;       // 30 minutos
    public const VENTANA_LOGIN = 900;      // 15 minutos
    public const ROLES = ['superadmin' => 'Superadministrador', 'editor' => 'Editor'];

    /** @var array<string,mixed>|null */
    private static $usuario;

    public static function esHttps(): bool
    {
        $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
        if ($https !== '' && $https !== 'off') {
            return true;
        }
        if ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443') {
            return true;
        }
        // CWP: nginx termina TLS y pasa la petición a Apache.
        return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }

    public static function iniciarSesion(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.gc_maxlifetime', (string) self::INACTIVIDAD);
        $ruta = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/'))), '/') . '/';
        session_name(self::NOMBRE_SESION);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => $ruta,
            'secure'   => self::esHttps(),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();

        $ahora = time();
        if (isset($_SESSION['uid'], $_SESSION['ultimo']) && $ahora - (int) $_SESSION['ultimo'] > self::INACTIVIDAD) {
            self::destruir();
            session_start();
            session_regenerate_id(true);
            $_SESSION['aviso'] = ['info', 'Tu sesión se cerró por inactividad. Vuelve a entrar.'];
        }
        $_SESSION['ultimo'] = $ahora;
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
    }

    public static function csrf(): string
    {
        return (string) ($_SESSION['csrf'] ?? '');
    }

    public static function csrfValido(): bool
    {
        $enviado = $_POST['csrf'] ?? '';
        return is_string($enviado) && self::csrf() !== '' && hash_equals(self::csrf(), $enviado);
    }

    /** Usuario autenticado y activo, releído de la base en cada petición (un cambio de rol o una baja aplican al instante). */
    public static function usuario(): ?array
    {
        if (self::$usuario !== null) {
            return self::$usuario;
        }
        if (empty($_SESSION['uid'])) {
            return null;
        }
        $u = Db::uno('SELECT id, usuario, nombre, rol, activo FROM bf_admin WHERE id = ?', [(int) $_SESSION['uid']]);
        if ($u === null || (int) $u['activo'] !== 1) {
            self::destruir();
            return null;
        }
        self::$usuario = $u;
        return $u;
    }

    public static function esSuperadmin(): bool
    {
        $u = self::usuario();
        return $u !== null && $u['rol'] === 'superadmin';
    }

    /**
     * Intenta iniciar sesión. Devuelve null si entró, o un mensaje de error genérico.
     */
    public static function entrar(string $usuario, string $password): ?string
    {
        $usuario = trim($usuario);
        $max = max(1, (int) Config::get('seguridad.login_intentos', 5));
        $porVisitante = RateLimiter::permitir('login-visitante', Http::huellaVisitante(), $max, self::VENTANA_LOGIN);
        $porUsuario = RateLimiter::permitir('login-usuario', mb_strtolower($usuario), $max, self::VENTANA_LOGIN);
        if (!$porVisitante || !$porUsuario) {
            return 'Demasiados intentos. Espera 15 minutos e inténtalo de nuevo.';
        }
        $generico = 'Usuario o contraseña incorrectos.';
        if ($usuario === '' || $password === '' || strlen($password) > 1024) {
            return $generico;
        }
        $fila = Db::uno('SELECT id, password_hash, activo FROM bf_admin WHERE usuario = ?', [$usuario]);
        // Con un usuario inexistente se verifica contra un hash recién generado: mismo costo, sin pistas por tiempo.
        $hash = $fila !== null ? (string) $fila['password_hash'] : password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        $ok = password_verify($password, $hash);
        if ($fila === null || !$ok || (int) $fila['activo'] !== 1) {
            return $generico;
        }
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            Db::ejecutar('UPDATE bf_admin SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), (int) $fila['id']]);
        }
        Db::ejecutar('UPDATE bf_admin SET ultimo_acceso = NOW() WHERE id = ?', [(int) $fila['id']]);
        // Un acceso correcto reinicia ambos contadores: en el campus muchas personas comparten IP (NAT)
        // y los accesos legítimos no deben agotar el cupo de los demás. El límite por usuario sigue protegiendo cada cuenta.
        RateLimiter::reiniciar('login-usuario', mb_strtolower($usuario), self::VENTANA_LOGIN);
        RateLimiter::reiniciar('login-visitante', Http::huellaVisitante(), self::VENTANA_LOGIN);

        session_regenerate_id(true);
        $_SESSION = [
            'uid'    => (int) $fila['id'],
            'ultimo' => time(),
            'csrf'   => bin2hex(random_bytes(32)),
        ];
        self::$usuario = null;
        return null;
    }

    /** Tras cambiar la propia contraseña: nuevo identificador de sesión. */
    public static function renovar(): void
    {
        session_regenerate_id(true);
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    public static function salir(): void
    {
        self::destruir();
    }

    private static function destruir(): void
    {
        self::$usuario = null;
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 3600,
                'path'     => $p['path'],
                'secure'   => (bool) $p['secure'],
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
            session_destroy();
        }
    }
}
