<?php
declare(strict_types=1);

namespace Buscador\Support;

final class Config
{
    /** @var array<string,mixed> */
    private static $datos = [];

    /** Carga private/config.php (o la ruta en BF_CONFIG, útil para pruebas). */
    public static function cargar(?string $ruta = null): void
    {
        $ruta = $ruta ?: (getenv('BF_CONFIG') ?: BF_PRIVATE . '/config.php');
        if (!is_file($ruta)) {
            $ruta = BF_PRIVATE . '/config.example.php';
        }
        $datos = require $ruta;
        self::$datos = is_array($datos) ? $datos : [];
    }

    /** @param array<string,mixed> $datos */
    public static function establecer(array $datos): void
    {
        self::$datos = array_replace_recursive(self::$datos, $datos);
    }

    /**
     * Lee un valor con notación de puntos: Config::get('db.host').
     * @param mixed $defecto
     * @return mixed
     */
    public static function get(string $clave, $defecto = null)
    {
        $valor = self::$datos;
        foreach (explode('.', $clave) as $parte) {
            if (!is_array($valor) || !array_key_exists($parte, $valor)) {
                return $defecto;
            }
            $valor = $valor[$parte];
        }
        return $valor;
    }
}
