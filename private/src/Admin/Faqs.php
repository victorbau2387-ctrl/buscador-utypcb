<?php
declare(strict_types=1);

namespace Buscador\Admin;

use Buscador\Search\Dinamicas;
use Buscador\Support\Db;

/**
 * Reglas y escritura de preguntas frecuentes (bf_faq + bf_variante).
 * Las mismas reglas se aplican en el formulario del panel y en private/bin/importar.php.
 */
final class Faqs
{
    public const ESTADOS = ['borrador' => 'Borrador', 'validada' => 'Validada', 'archivada' => 'Archivada'];
    public const TIPOS = ['fija' => 'Fija (texto)', 'dinamica' => 'Dinámica (datos del sitio)'];
    public const MIN_VARIANTES_VALIDADA = 5;

    /** Separa variantes (una por línea o separadas por "|"), quita vacías y duplicadas. @return string[] */
    public static function variantesDeTexto(string $texto, string $separador = "\n"): array
    {
        $vistas = [];
        $res = [];
        foreach (explode($separador, str_replace("\r", '', $texto)) as $v) {
            $v = trim((string) preg_replace('/\s+/u', ' ', $v));
            $clave = mb_strtolower($v);
            if ($v === '' || isset($vistas[$clave])) {
                continue;
            }
            $vistas[$clave] = true;
            $res[] = $v;
        }
        return $res;
    }

    /**
     * Valida una pregunta normalizada. Devuelve errores por campo (vacío si todo está bien).
     * @param array<string,mixed> $f
     * @return array<string,string>
     */
    public static function validar(array $f, bool $areaExiste = true): array
    {
        $err = [];
        $largo = static function (string $campo, int $max) use ($f, &$err): void {
            if (mb_strlen((string) ($f[$campo] ?? '')) > $max) {
                $err[$campo] = 'Máximo ' . $max . ' caracteres.';
            }
        };
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) $f['slug'])) {
            $err['slug'] = 'Usa minúsculas, números y guiones (ej. becas-requisitos).';
        }
        $largo('slug', 80);
        if (!preg_match('/^[a-z0-9_-]{1,40}$/', (string) $f['categoria'])) {
            $err['categoria'] = 'Categoría obligatoria: minúsculas, números, guion o guion bajo (máx. 40).';
        }
        if (trim((string) $f['pregunta']) === '') {
            $err['pregunta'] = 'La pregunta es obligatoria.';
        }
        $largo('pregunta', 255);
        if (!isset(self::TIPOS[$f['tipo']])) {
            $err['tipo'] = 'Tipo inválido.';
        } elseif ($f['tipo'] === 'dinamica') {
            if (!isset(Dinamicas::HANDLERS[(string) $f['handler']])) {
                $err['handler'] = 'Elige un handler existente para las preguntas dinámicas.';
            }
        } elseif (trim((string) $f['respuesta']) === '') {
            $err['respuesta'] = 'La respuesta es obligatoria en las preguntas fijas.';
        }
        if ($f['tipo'] === 'fija' && (string) $f['handler'] !== '') {
            $err['handler'] = 'Las preguntas fijas no llevan handler.';
        }
        $largo('respuesta', 60000);
        $largo('palabras_clave', 500);
        if ((string) $f['carrera'] !== '' && !preg_match('/^[a-z0-9-]{1,80}$/', (string) $f['carrera'])) {
            $err['carrera'] = 'La carrera se indica con su slug (minúsculas y guiones).';
        }
        $url = (string) $f['fuente_url'];
        if (strncmp($url, 'https://', 8) !== 0 || filter_var($url, FILTER_VALIDATE_URL) === false || strlen($url) > 255) {
            $err['fuente_url'] = 'La fuente es obligatoria y debe ser una URL https:// válida.';
        }
        if (!$areaExiste) {
            $err['area'] = 'El área no existe.';
        }
        if (!isset(self::ESTADOS[$f['estado']])) {
            $err['estado'] = 'Estado inválido.';
        }
        foreach ($f['variantes'] as $v) {
            if (mb_strlen($v) > 255) {
                $err['variantes'] = 'Cada variante admite como máximo 255 caracteres.';
            }
        }
        $largo('validado_por', 120);
        $fecha = (string) $f['validado_en'];
        if ($fecha !== '') {
            $d = \DateTime::createFromFormat('!Y-m-d', $fecha);
            if ($d === false || $d->format('Y-m-d') !== $fecha) {
                $err['validado_en'] = 'Fecha inválida (AAAA-MM-DD).';
            } elseif ($d > new \DateTime('tomorrow')) {
                $err['validado_en'] = 'La fecha de validación no puede estar en el futuro.';
            }
        }
        if ($f['estado'] === 'validada') {
            if (count($f['variantes']) < self::MIN_VARIANTES_VALIDADA) {
                $err['variantes'] = 'Para marcarla validada necesita al menos ' . self::MIN_VARIANTES_VALIDADA . ' variantes (tiene ' . count($f['variantes']) . ').';
            }
            if (trim((string) $f['validado_por']) === '') {
                $err['validado_por'] = 'Indica quién la validó.';
            }
            if ($fecha === '') {
                $err['validado_en'] = 'Indica la fecha de validación.';
            }
        }
        return $err;
    }

    /** Pregunta vacía para el formulario. @return array<string,mixed> */
    public static function vacia(): array
    {
        return [
            'id' => 0, 'slug' => '', 'categoria' => '', 'pregunta' => '', 'respuesta' => '', 'tipo' => 'fija',
            'handler' => '', 'palabras_clave' => '', 'carrera' => '', 'fuente_url' => 'https://', 'area_id' => null,
            'estado' => 'borrador', 'validado_por' => '', 'validado_en' => '', 'variantes' => [],
        ];
    }

    /** @return array<string,mixed>|null */
    public static function obtener(int $id): ?array
    {
        $f = Db::uno('SELECT * FROM bf_faq WHERE id = ?', [$id]);
        if ($f === null) {
            return null;
        }
        $f['variantes'] = array_map(static function ($v) {
            return (string) $v['texto'];
        }, Db::todos('SELECT texto FROM bf_variante WHERE faq_id = ? ORDER BY id', [$id]));
        foreach (['handler', 'carrera', 'validado_por', 'validado_en'] as $c) {
            $f[$c] = (string) ($f[$c] ?? '');
        }
        return $f;
    }

    public static function slugEnUso(string $slug, int $exceptoId): bool
    {
        return Db::uno('SELECT id FROM bf_faq WHERE slug = ? AND id <> ?', [$slug, $exceptoId]) !== null;
    }

    /**
     * Inserta o actualiza (por id, o por slug si $id = 0 y $porSlug) y reemplaza las variantes.
     * No abre transacción: quien llama decide (el formulario usa guardar(), el importador agrupa todo).
     * @param array<string,mixed> $f
     */
    public static function escribir(array $f, int $id = 0, bool $porSlug = false): int
    {
        $valores = [
            (string) $f['slug'], (string) $f['categoria'], trim((string) $f['pregunta']), (string) $f['respuesta'],
            (string) $f['tipo'], $f['tipo'] === 'dinamica' ? (string) $f['handler'] : null,
            trim((string) $f['palabras_clave']), (string) $f['carrera'] !== '' ? (string) $f['carrera'] : null,
            (string) $f['fuente_url'], $f['area_id'] !== null ? (int) $f['area_id'] : null, (string) $f['estado'],
            trim((string) $f['validado_por']) !== '' ? trim((string) $f['validado_por']) : null,
            (string) $f['validado_en'] !== '' ? (string) $f['validado_en'] : null,
        ];
        if ($id === 0 && $porSlug) {
            $fila = Db::uno('SELECT id FROM bf_faq WHERE slug = ?', [(string) $f['slug']]);
            $id = $fila !== null ? (int) $fila['id'] : 0;
        }
        if ($id > 0) {
            $valores[] = $id;
            Db::ejecutar(
                'UPDATE bf_faq SET slug = ?, categoria = ?, pregunta = ?, respuesta = ?, tipo = ?, handler = ?, palabras_clave = ?,
                        carrera = ?, fuente_url = ?, area_id = ?, estado = ?, validado_por = ?, validado_en = ?
                 WHERE id = ?',
                $valores
            );
        } else {
            Db::ejecutar(
                'INSERT INTO bf_faq (slug, categoria, pregunta, respuesta, tipo, handler, palabras_clave, carrera, fuente_url,
                                     area_id, estado, validado_por, validado_en)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                $valores
            );
            $id = Db::ultimoId();
        }
        Db::ejecutar('DELETE FROM bf_variante WHERE faq_id = ?', [$id]);
        foreach ($f['variantes'] as $v) {
            Db::ejecutar('INSERT INTO bf_variante (faq_id, texto) VALUES (?, ?)', [$id, $v]);
        }
        return $id;
    }

    /** Guarda en una transacción. @param array<string,mixed> $f */
    public static function guardar(array $f, int $id): int
    {
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $id = self::escribir($f, $id);
            $pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Agrega una variante si no existe ya (sin distinguir mayúsculas). */
    public static function agregarVariante(int $faqId, string $texto): bool
    {
        $texto = mb_substr(trim((string) preg_replace('/\s+/u', ' ', $texto)), 0, 255);
        if ($texto === '') {
            return false;
        }
        foreach (Db::todos('SELECT texto FROM bf_variante WHERE faq_id = ?', [$faqId]) as $v) {
            if (mb_strtolower((string) $v['texto']) === mb_strtolower($texto)) {
                return false;
            }
        }
        Db::ejecutar('INSERT INTO bf_variante (faq_id, texto) VALUES (?, ?)', [$faqId, $texto]);
        return true;
    }

    /** @return string[] */
    public static function categorias(): array
    {
        return array_map(static function ($f) {
            return (string) $f['categoria'];
        }, Db::todos('SELECT DISTINCT categoria FROM bf_faq ORDER BY categoria'));
    }

    /** Lista para selectores: id, categoria, pregunta (sin archivadas). @return array<int,array<string,mixed>> */
    public static function paraSelector(): array
    {
        return Db::todos("SELECT id, categoria, pregunta FROM bf_faq WHERE estado <> 'archivada' ORDER BY categoria, pregunta");
    }
}
