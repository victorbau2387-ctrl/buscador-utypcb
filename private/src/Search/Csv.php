<?php
declare(strict_types=1);

namespace Buscador\Search;

/**
 * Lectura de la base de conocimiento en CSV (formato revisable en Excel).
 *
 * conocimiento/preguntas.csv  (UTF-8, separador coma, variantes separadas por "|")
 *   slug,categoria,pregunta,variantes,respuesta,tipo,handler,palabras_clave,carrera,fuente_url,area,estado,validado_por,validado_en
 * conocimiento/areas.csv
 *   clave,nombre,correo,telefono,url
 */
final class Csv
{
    public const COLUMNAS_FAQ = [
        'slug', 'categoria', 'pregunta', 'variantes', 'respuesta', 'tipo', 'handler', 'palabras_clave',
        'carrera', 'fuente_url', 'area', 'estado', 'validado_por', 'validado_en',
    ];
    public const COLUMNAS_AREA = ['clave', 'nombre', 'correo', 'telefono', 'url'];

    /** @return array<int,array<string,string>> */
    public static function leer(string $ruta): array
    {
        $fh = fopen($ruta, 'rb');
        if ($fh === false) {
            throw new \RuntimeException('No se pudo abrir ' . $ruta);
        }
        $encabezado = fgetcsv($fh, 0, ',', '"', '\\');
        if (!$encabezado) {
            fclose($fh);
            return [];
        }
        $encabezado[0] = (string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $encabezado[0]); // BOM de Excel
        $encabezado = array_map('trim', $encabezado);
        $filas = [];
        while (($f = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
            if ($f === [null] || trim(implode('', $f)) === '') {
                continue;
            }
            $fila = [];
            foreach ($encabezado as $i => $col) {
                $fila[$col] = trim((string) ($f[$i] ?? ''));
            }
            $filas[] = $fila;
        }
        fclose($fh);
        return $filas;
    }

    /**
     * Convierte el CSV en filas como las que entrega Repositorio::faqsActivas() (sin base de datos).
     * Útil para pruebas y para la evaluación fuera de línea.
     * @return array<int,array<string,mixed>>
     */
    public static function faqsEnMemoria(string $rutaFaq, ?string $rutaAreas = null): array
    {
        $areas = [];
        if ($rutaAreas !== null && is_file($rutaAreas)) {
            foreach (self::leer($rutaAreas) as $a) {
                $areas[$a['clave']] = $a;
            }
        }
        $res = [];
        $id = 0;
        foreach (self::leer($rutaFaq) as $f) {
            if (($f['estado'] ?? '') === 'archivada') {
                continue;
            }
            $a = $areas[$f['area'] ?? ''] ?? null;
            $res[] = [
                'id'             => ++$id,
                'slug'           => $f['slug'],
                'categoria'      => $f['categoria'],
                'pregunta'       => $f['pregunta'],
                'respuesta'      => $f['respuesta'],
                'tipo'           => $f['tipo'] ?: 'fija',
                'handler'        => $f['handler'] ?: null,
                'palabras_clave' => $f['palabras_clave'],
                'carrera'        => $f['carrera'] ?: null,
                'fuente_url'     => $f['fuente_url'],
                'estado'         => $f['estado'] ?: 'borrador',
                'validado_por'   => $f['validado_por'] ?: null,
                'variantes'      => array_values(array_filter(array_map('trim', explode('|', $f['variantes'])), 'strlen')),
                'area_nombre'    => $a['nombre'] ?? null,
                'area_correo'    => $a['correo'] ?? null,
                'area_telefono'  => $a['telefono'] ?? null,
                'area_url'       => $a['url'] ?? null,
            ];
        }
        return $res;
    }
}
