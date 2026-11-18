<?php
declare(strict_types=1);

namespace Buscador\Search;

/**
 * Normalización de texto en español para búsqueda tolerante:
 * minúsculas, sin acentos, sin signos, abreviaturas de chat, sinónimos,
 * plegado fonético (b/v, s/z/c, j/g, k/c/qu, y/ll, h muda) y raíz ligera.
 */
final class Texto
{
    /** @var array<string,mixed>|null */
    private static $lexico;
    /** @var array<string,string> */
    private static $sinonimos = [];
    /** @var array<string,bool> */
    private static $vacias = [];

    private const ACENTOS = [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u', 'â' => 'a', 'ê' => 'e', 'ô' => 'o',
    ];

    /** @return array<string,mixed> */
    public static function lexico(): array
    {
        if (self::$lexico === null) {
            $lex = require BF_PRIVATE . '/data/lexico.php';
            $limpio = [];
            foreach (['expansiones', 'frases', 'alias_carrera', 'no_ofertadas'] as $sec) {
                $limpio[$sec] = [];
                foreach ($lex[$sec] ?? [] as $k => $v) {
                    $limpio[$sec][self::normalizar((string) $k)] = $sec === 'no_ofertadas' ? $v : self::normalizar((string) $v);
                }
            }
            foreach ($lex['sinonimos'] ?? [] as $grupo) {
                $canon = self::normalizar((string) $grupo[0]);
                foreach ($grupo as $p) {
                    self::$sinonimos[self::normalizar((string) $p)] = $canon;
                }
            }
            foreach ($lex['vacias'] ?? [] as $p) {
                $n = self::normalizar((string) $p);
                self::$vacias[$n] = true;
                self::$vacias[self::fonetica($n)] = true; // "kiero" = "quiero"
            }
            self::$lexico = $limpio;
        }
        return self::$lexico;
    }

    /** Minúsculas, sin acentos ni signos, espacios simples, letras repetidas reducidas ("holaaa" → "hola"). */
    public static function normalizar(string $texto): string
    {
        $t = mb_strtolower($texto, 'UTF-8');
        $t = strtr($t, self::ACENTOS);
        $t = (string) preg_replace('/[^a-z0-9]+/u', ' ', $t);
        $t = (string) preg_replace('/([a-z])\1{2,}/', '$1', $t);
        return trim((string) preg_replace('/\s+/', ' ', $t));
    }

    /**
     * Palabras normalizadas tras expansiones y frases (antes de quitar vacías).
     * @return string[]
     */
    public static function palabras(string $texto): array
    {
        $lex = self::lexico();
        $t = self::normalizar($texto);
        if ($t === '') {
            return [];
        }
        $palabras = explode(' ', $t);
        foreach ($palabras as $i => $p) {
            if (isset($lex['expansiones'][$p])) {
                $palabras[$i] = $lex['expansiones'][$p];
            }
        }
        $t = ' ' . implode(' ', $palabras) . ' ';
        foreach ($lex['frases'] as $frase => $reemplazo) {
            if (strpos($t, ' ' . $frase . ' ') !== false) {
                $t = str_replace(' ' . $frase . ' ', ' ' . $reemplazo . ' ', $t);
            }
        }
        return array_values(array_filter(explode(' ', trim($t)), 'strlen'));
    }

    /**
     * Términos de búsqueda: sin vacías, con sinónimos, plegado fonético y raíz.
     * @return string[]
     */
    public static function terminos(string $texto): array
    {
        return array_map(static function ($par) {
            return $par[1];
        }, self::terminosConForma($texto));
    }

    /**
     * Igual que terminos(), pero conserva la palabra plegada completa (antes de truncar),
     * que es la que se usa para corregir faltas de ortografía.
     * @return array<int,array{0:string,1:string}> [palabra plegada, raíz]
     */
    public static function terminosConForma(string $texto): array
    {
        self::lexico();
        $res = [];
        foreach (self::palabras($texto) as $p) {
            if (isset(self::$vacias[$p]) || isset(self::$vacias[self::fonetica($p)])) {
                continue;
            }
            if (isset(self::$sinonimos[$p])) {
                $p = self::$sinonimos[$p];
            }
            $f = self::fonetica($p);
            $r = self::raiz($f);
            if ($r !== '') {
                $res[] = [$f, $r];
            }
        }
        return $res;
    }

    /** Plegado fonético para tolerar faltas de ortografía comunes. */
    public static function fonetica(string $p): string
    {
        if (ctype_digit($p)) {
            return $p;
        }
        $p = str_replace(['ch', 'll', 'qu', 'ce', 'ci', 'ge', 'gi', 'v', 'z', 'w'], ['X', 'y', 'k', 'se', 'si', 'je', 'ji', 'b', 's', 'u'], $p);
        $p = str_replace('c', 'k', $p);
        $p = str_replace('h', '', $p);
        $p = str_replace('X', 'ch', $p);
        return (string) preg_replace('/(.)\1+/', '$1', $p);
    }

    /** Raíz ligera: quita plurales y trunca a 6 caracteres. */
    public static function raiz(string $p): string
    {
        $n = strlen($p);
        if ($n > 4 && substr($p, -3) === 'ses') {
            $p = substr($p, 0, -2);
        } elseif ($n > 5 && substr($p, -2) === 'es' && strpos('aeiou', $p[$n - 3]) === false) {
            $p = substr($p, 0, -2);
        } elseif ($n > 3 && substr($p, -1) === 's') {
            $p = substr($p, 0, -1);
        }
        return strlen($p) > 6 ? substr($p, 0, 6) : $p;
    }

    public static function slug(string $texto): string
    {
        return trim(str_replace(' ', '-', self::normalizar($texto)), '-');
    }
}
