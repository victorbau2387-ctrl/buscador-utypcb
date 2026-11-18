<?php
declare(strict_types=1);

namespace Buscador\Admin;

use Buscador\Search\Csv;
use Buscador\Support\Db;

/**
 * Importa conocimiento/areas.csv y conocimiento/preguntas.csv a la base.
 * Upsert por clave (áreas) y por slug (preguntas), reemplaza variantes, todo en una transacción.
 * Si hay un solo error, no se escribe nada.
 */
final class Importador
{
    /** @var array<int,string> */
    public $errores = [];
    /** @var array<int,string> */
    public $advertencias = [];
    /** @var array<string,int> */
    public $conteo = ['areas' => 0, 'preguntas' => 0, 'variantes' => 0];

    /** @var array<string,array<string,string>> */
    private $areas = [];
    /** @var array<int,array<string,mixed>> */
    private $faqs = [];

    /**
     * Lee y valida ambos archivos. $areasEnBase: claves que ya existen en bf_area (null si no hay conexión).
     * @param string[]|null $areasEnBase
     */
    public function validar(string $rutaAreas, string $rutaFaqs, ?array $areasEnBase): bool
    {
        $this->errores = [];
        $this->advertencias = [];
        $this->areas = [];
        $this->faqs = [];

        if (is_file($rutaAreas)) {
            $filas = $this->leer($rutaAreas, Csv::COLUMNAS_AREA, 'areas.csv');
            foreach ($filas as $i => $f) {
                $n = 'areas.csv fila ' . ($i + 2);
                $a = [];
                foreach (Csv::COLUMNAS_AREA as $c) {
                    $a[$c] = Exportador::desproteger((string) ($f[$c] ?? ''));
                }
                foreach (Areas::validar($a) as $campo => $msg) {
                    $this->errores[] = $n . ' [' . $campo . ']: ' . $msg;
                }
                if (isset($this->areas[$a['clave']])) {
                    $this->errores[] = $n . ': la clave "' . $a['clave'] . '" está repetida.';
                }
                $this->areas[$a['clave']] = $a;
            }
        } else {
            $this->advertencias[] = 'No se encontró ' . $rutaAreas . '; se usarán solo las áreas que ya existen en la base.';
        }

        $clavesValidas = array_merge(array_keys($this->areas), $areasEnBase ?? []);
        $filas = $this->leer($rutaFaqs, Csv::COLUMNAS_FAQ, 'preguntas.csv');
        $slugs = [];
        foreach ($filas as $i => $fila) {
            $n = 'preguntas.csv fila ' . ($i + 2);
            $vacias = [];
            foreach (['slug', 'categoria', 'pregunta', 'fuente_url'] as $c) {
                if (($fila[$c] ?? '') === '') {
                    $vacias[] = $c;
                }
            }
            if ($vacias) {
                $this->errores[] = $n . ': faltan columnas obligatorias: ' . implode(', ', $vacias) . '.';
            }
            $g = static function (string $c) use ($fila): string {
                return Exportador::desproteger((string) ($fila[$c] ?? ''));
            };
            $f = [
                'slug'           => $g('slug'),
                'categoria'      => $g('categoria'),
                'pregunta'       => $g('pregunta'),
                'respuesta'      => $g('respuesta'),
                'tipo'           => $g('tipo') !== '' ? $g('tipo') : 'fija',
                'handler'        => $g('handler'),
                'palabras_clave' => $g('palabras_clave'),
                'carrera'        => $g('carrera'),
                'fuente_url'     => $g('fuente_url'),
                'area'           => $g('area'),
                'area_id'        => null,
                'estado'         => $g('estado') !== '' ? $g('estado') : 'borrador',
                'validado_por'   => $g('validado_por'),
                'validado_en'    => $g('validado_en'),
                'variantes'      => Faqs::variantesDeTexto($g('variantes'), '|'),
            ];
            $areaExiste = $f['area'] === '' || in_array($f['area'], $clavesValidas, true);
            foreach (Faqs::validar($f, $areaExiste) as $campo => $msg) {
                if ($vacias && in_array($campo, $vacias, true)) {
                    continue; // ya se reportó como columna faltante
                }
                if ($campo === 'area') {
                    $msg = 'el área "' . $f['area'] . '" no existe en areas.csv' . ($areasEnBase !== null ? ' ni en la base' : '') . '.';
                }
                $this->errores[] = $n . ' (' . ($f['slug'] !== '' ? $f['slug'] : 'sin slug') . ') [' . $campo . ']: ' . $msg;
            }
            if ($f['estado'] !== 'validada' && count($f['variantes']) < Faqs::MIN_VARIANTES_VALIDADA) {
                $this->advertencias[] = $n . ' (' . $f['slug'] . '): tiene ' . count($f['variantes']) . ' variantes; necesitará al menos '
                    . Faqs::MIN_VARIANTES_VALIDADA . ' para poder validarse.';
            }
            if (isset($slugs[$f['slug']]) && $f['slug'] !== '') {
                $this->errores[] = $n . ': el slug "' . $f['slug'] . '" está repetido (fila ' . $slugs[$f['slug']] . ').';
            }
            $slugs[$f['slug']] = $i + 2;
            $this->faqs[] = $f;
        }
        return !$this->errores;
    }

    /** Escribe en la base (solo si validar() no dejó errores). */
    public function importar(): void
    {
        if ($this->errores) {
            throw new \LogicException('No se importa con errores de validación.');
        }
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            foreach ($this->areas as $a) {
                Areas::escribir($a);
                $this->conteo['areas']++;
            }
            $ids = Areas::idsPorClave();
            foreach ($this->faqs as $f) {
                $f['area_id'] = $f['area'] !== '' ? ($ids[$f['area']] ?? null) : null;
                Faqs::escribir($f, 0, true);
                $this->conteo['preguntas']++;
                $this->conteo['variantes'] += count($f['variantes']);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function totalPreguntas(): int
    {
        return count($this->faqs);
    }

    public function totalAreas(): int
    {
        return count($this->areas);
    }

    /**
     * Lee el CSV y comprueba que el encabezado tenga todas las columnas esperadas.
     * @param string[] $esperadas
     * @return array<int,array<string,string>>
     */
    private function leer(string $ruta, array $esperadas, string $nombre): array
    {
        if (!is_file($ruta)) {
            $this->errores[] = 'No existe ' . $ruta . '.';
            return [];
        }
        $filas = Csv::leer($ruta);
        $fh = fopen($ruta, 'rb');
        $enc = $fh ? fgetcsv($fh, 0, ',', '"', '\\') : false;
        if ($fh) {
            fclose($fh);
        }
        $enc = is_array($enc) ? array_map('trim', $enc) : [];
        if ($enc) {
            $enc[0] = (string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $enc[0]);
        }
        $faltan = array_diff($esperadas, $enc);
        if ($faltan) {
            $this->errores[] = $nombre . ': faltan columnas en el encabezado: ' . implode(', ', $faltan) . '.';
            return [];
        }
        // Filas con menos columnas que el encabezado (comas de menos o comillas mal cerradas).
        $fh = fopen($ruta, 'rb');
        if ($fh) {
            fgetcsv($fh, 0, ',', '"', '\\');
            $i = 0;
            while (($f = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
                if ($f === [null] || trim(implode('', $f)) === '') {
                    continue;
                }
                if (count($f) !== count($enc)) {
                    $this->errores[] = $nombre . ' fila ' . ($i + 2) . ': tiene ' . count($f) . ' columnas y el encabezado ' . count($enc) . ' (revisa comas y comillas).';
                }
                $i++;
            }
            fclose($fh);
        }
        return $filas;
    }
}
