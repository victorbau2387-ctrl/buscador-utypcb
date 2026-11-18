<?php
declare(strict_types=1);

namespace Buscador\Search;

use Buscador\Site\DatosSitio;

/**
 * Detecta en la consulta la carrera mencionada (activa o no ofertada).
 * Las carreras activas salen de carreras_data.php; los alias y las no ofertadas de data/lexico.php.
 */
final class Carreras
{
    /** Palabras de los nombres oficiales que no identifican a una carrera. */
    private const GENERICAS = [
        'ingenieria', 'licenciatura', 'en', 'de', 'y', 'la', 'el', 'los', 'tecnico', 'superior', 'universitario',
        'desarrollo', 'innovacion', 'digital', 'digitales', 'procesos', 'manejo', 'entornos', 'negocios', 'virtuales',
        'informacion', // "quiero información sobre becas" no es la carrera de Tecnologías de la Información
    ];

    /** Palabras que indican que se pregunta por una oferta académica. */
    private const INTENCION_CARRERA = [
        'carrera', 'carreras', 'licenciatura', 'ingenieria', 'estudiar', 'estudio', 'ofrecen', 'tienen', 'hay',
        'imparten', 'existe', 'oferta', 'programa', 'quiero', 'busco',
    ];

    /** Palabras que indican un servicio (de salud, atención) y no una oferta académica. */
    private const SERVICIO = [
        'servicio', 'servicios', 'medico', 'medica', 'consultorio', 'clinica', 'atencion', 'cita', 'citas', 'salud', 'area', 'departamento',
    ];

    /** @var array<string,array<string,mixed>>|null slug => carrera con 'claves' */
    private static $activas;

    /** @return array<string,array<string,mixed>> */
    public static function activas(): array
    {
        if (self::$activas !== null) {
            return self::$activas;
        }
        $carreras = DatosSitio::carreras();
        $conteo = [];
        $palabras = [];
        foreach ($carreras as $c) {
            $ws = array_unique(explode(' ', Texto::normalizar($c['nombre'] . ' ' . ($c['tsu'] ?? ''))));
            $ws = array_filter($ws, static function ($w) {
                return strlen($w) > 2 && !in_array($w, self::GENERICAS, true);
            });
            $palabras[$c['slug']] = $ws;
            foreach ($ws as $w) {
                $conteo[$w] = ($conteo[$w] ?? 0) + 1;
            }
        }
        self::$activas = [];
        foreach ($carreras as $c) {
            // Solo palabras exclusivas de esa carrera la identifican.
            $c['claves'] = array_values(array_filter($palabras[$c['slug']], static function ($w) use ($conteo) {
                return $conteo[$w] === 1;
            }));
            self::$activas[$c['slug']] = $c;
        }
        return self::$activas;
    }

    /** Devuelve el slug de la carrera activa mencionada, o null. */
    public static function detectar(string $consulta): ?string
    {
        $lex = Texto::lexico();
        $palabras = Texto::palabras($consulta);
        $mejor = null;
        $mejorPuntos = 0;
        foreach (self::activas() as $slug => $c) {
            $puntos = 0;
            foreach ($palabras as $p) {
                $p2 = $lex['alias_carrera'][$p] ?? $p;
                if (in_array($p2, $c['claves'], true) || in_array($p, $c['claves'], true)) {
                    $puntos++;
                }
            }
            if ($puntos > $mejorPuntos) {
                $mejor = $slug;
                $mejorPuntos = $puntos;
            }
        }
        return $mejor;
    }

    /**
     * Si la consulta trata de una carrera que la universidad no ofrece, devuelve su nombre.
     * "¿tienen enfermería?" → "Enfermería";  "¿hay servicio de enfermería?" → null.
     */
    public static function noOfertada(string $consulta): ?string
    {
        $lex = Texto::lexico();
        $palabras = Texto::palabras($consulta);
        // Si habla de un servicio de salud ("servicio de enfermería", "consultorio de psicología"), no es una carrera.
        if (array_intersect($palabras, self::SERVICIO)) {
            return null;
        }
        foreach ($palabras as $p) {
            if (isset($lex['no_ofertadas'][$p])) {
                $significativas = count(Texto::terminos($consulta));
                $intencion = (bool) array_intersect($palabras, self::INTENCION_CARRERA);
                if ($intencion || $significativas <= 2) {
                    return (string) $lex['no_ofertadas'][$p];
                }
            }
        }
        return null;
    }

    /** @return array<string,mixed>|null */
    public static function porSlug(?string $slug): ?array
    {
        if ($slug === null) {
            return null;
        }
        $a = self::activas();
        return $a[$slug] ?? null;
    }

    public static function limpiarCache(): void
    {
        self::$activas = null;
    }
}
