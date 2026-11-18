<?php
declare(strict_types=1);

namespace Buscador\Admin;

use Buscador\Search\Csv;
use Buscador\Support\Db;

/**
 * Exporta la base de conocimiento con el mismo formato que conocimiento/preguntas.csv
 * (columnas Csv::COLUMNAS_FAQ, variantes separadas por "|"), con BOM UTF-8 para Excel.
 * El archivo se puede volver a importar con private/bin/importar.php.
 */
final class Exportador
{
    /** Caracteres con los que Excel interpretaría la celda como fórmula. */
    private const INICIO_FORMULA = ['=', '+', '-', '@', "\t", "\r"];

    /** @return array<int,array<int,string>> filas en el orden de Csv::COLUMNAS_FAQ */
    public static function filas(bool $incluirArchivadas = true): array
    {
        $faqs = Db::todos(
            'SELECT f.*, a.clave AS area FROM bf_faq f LEFT JOIN bf_area a ON a.id = f.area_id '
            . ($incluirArchivadas ? '' : "WHERE f.estado <> 'archivada' ")
            . 'ORDER BY f.categoria, f.slug'
        );
        $variantes = [];
        foreach (Db::todos('SELECT faq_id, texto FROM bf_variante ORDER BY id') as $v) {
            $variantes[(int) $v['faq_id']][] = str_replace('|', '/', (string) $v['texto']);
        }
        $res = [];
        foreach ($faqs as $f) {
            $f['variantes'] = implode('|', $variantes[(int) $f['id']] ?? []);
            $fila = [];
            foreach (Csv::COLUMNAS_FAQ as $col) {
                $fila[] = self::celda((string) ($f[$col] ?? ''));
            }
            $res[] = $fila;
        }
        return $res;
    }

    /** @param resource $fh */
    public static function escribir($fh, bool $incluirArchivadas = true): int
    {
        fwrite($fh, "\xEF\xBB\xBF");
        fputcsv($fh, Csv::COLUMNAS_FAQ, ',', '"', '\\');
        $n = 0;
        foreach (self::filas($incluirArchivadas) as $fila) {
            fputcsv($fh, $fila, ',', '"', '\\');
            $n++;
        }
        return $n;
    }

    /** Evita inyección de fórmulas en Excel anteponiendo un apóstrofo (el importador lo retira). */
    public static function celda(string $v): string
    {
        if ($v !== '' && in_array($v[0], self::INICIO_FORMULA, true)) {
            return "'" . $v;
        }
        return $v;
    }

    /** Inverso de celda(): quita el apóstrofo protector al importar. */
    public static function desproteger(string $v): string
    {
        if (strlen($v) > 1 && $v[0] === "'" && in_array($v[1], self::INICIO_FORMULA, true)) {
            return substr($v, 1);
        }
        return $v;
    }
}
