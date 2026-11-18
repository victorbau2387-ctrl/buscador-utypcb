<?php
declare(strict_types=1);

namespace Buscador\Site;

use Buscador\Search\Texto;
use Buscador\Support\Config;

/**
 * Lee los arreglos PHP del sitio institucional con require_once (única fuente de verdad).
 * No copia ni duplica la información: si el sitio cambia sus datos, el buscador los ve al instante.
 * Es tolerante a variaciones de formato: acepta $VAR = ['clave' => [...]] o $VAR = [...].
 */
final class DatosSitio
{
    /** @var array<string,array<int,array<string,mixed>>> */
    private static $cache = [];

    /**
     * @param string $archivo  p. ej. 'calendario_data.php'
     * @param string $variable p. ej. 'CAL'
     * @param string $clave    clave interna de la lista, p. ej. 'eventos'
     * @return array<int,array<string,mixed>>
     */
    private static function lista(string $archivo, string $variable, string $clave): array
    {
        $id = $archivo . ':' . $clave;
        if (isset(self::$cache[$id])) {
            return self::$cache[$id];
        }
        $ruta = rtrim((string) Config::get('sitio.ruta_datos', ''), '/\\') . '/' . $archivo;
        $lista = [];
        if (is_file($ruta)) {
            $datos = (static function (string $ruta, string $variable) {
                require $ruta; // require (no _once) dentro de un ámbito propio para obtener la variable
                return isset($$variable) ? $$variable : null;
            })($ruta, $variable);
            if (is_array($datos)) {
                $lista = isset($datos[$clave]) && is_array($datos[$clave]) ? $datos[$clave] : $datos;
            }
        }
        $lista = array_values(array_filter($lista, 'is_array'));
        return self::$cache[$id] = $lista;
    }

    /** @return array<int,array<string,mixed>> nombre, tsu, nivel, url, slug */
    public static function carreras(): array
    {
        $res = [];
        foreach (self::lista('carreras_data.php', 'CARRERAS', 'carreras') as $c) {
            if (empty($c['nombre'])) {
                continue;
            }
            $c['slug'] = $c['slug'] ?? Texto::slug((string) $c['nombre']);
            $res[] = $c;
        }
        return $res;
    }

    /** @return array<int,array<string,mixed>> fecha (Y-m-d), titulo, tipo */
    public static function eventos(): array
    {
        $ev = self::lista('calendario_data.php', 'CAL', 'eventos');
        usort($ev, static function ($a, $b) {
            return strcmp((string) ($a['fecha'] ?? ''), (string) ($b['fecha'] ?? ''));
        });
        return $ev;
    }

    /** @return array<int,array<string,mixed>> */
    public static function noticias(): array
    {
        $n = self::lista('noticias_data.php', 'NOTICIAS', 'noticias');
        usort($n, static function ($a, $b) {
            return strcmp((string) ($b['fecha'] ?? ''), (string) ($a['fecha'] ?? ''));
        });
        return $n;
    }

    /** @return array<int,array<string,mixed>> */
    public static function deportes(): array
    {
        $d = self::lista('deportes_data.php', 'DEPORTES', 'encuentros');
        usort($d, static function ($a, $b) {
            return strcmp((string) ($b['fecha'] ?? ''), (string) ($a['fecha'] ?? ''));
        });
        return $d;
    }

    /** @return array<int,array<string,mixed>> */
    public static function tripticos(): array
    {
        return self::lista('tripticos_data.php', 'TRIPTICOS', 'tripticos');
    }

    public static function url(string $ruta): string
    {
        if (preg_match('#^https?://#', $ruta)) {
            return $ruta;
        }
        return rtrim((string) Config::get('sitio.url_base', ''), '/') . '/' . ltrim($ruta, '/');
    }

    /** Solo para pruebas. */
    public static function limpiarCache(): void
    {
        self::$cache = [];
    }
}
