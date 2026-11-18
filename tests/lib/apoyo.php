<?php
// Utilidades compartidas por las pruebas unitarias.
declare(strict_types=1);

use Buscador\Search\Buscador;
use Buscador\Search\Csv;
use Buscador\Search\Indice;
use Buscador\Support\Config;

/**
 * Preguntas de prueba ESTABLES (copia congelada en tests/fixtures/conocimiento/).
 * Las pruebas del motor no dependen de conocimiento/*.csv, que cambia con la base de conocimiento real.
 * @return array<int,array<string,mixed>>
 */
function faqsPrueba(): array
{
    static $faqs = null;
    if ($faqs === null) {
        $dir = __DIR__ . '/../fixtures/conocimiento';
        $faqs = Csv::faqsEnMemoria($dir . '/preguntas.csv', $dir . '/areas.csv');
    }
    return $faqs;
}

function indicePrueba(): Indice
{
    return new Indice(faqsPrueba());
}

function motorPrueba(): Buscador
{
    return new Buscador(indicePrueba());
}

/** Slug de la pregunta respondida (o null). */
function slugRespondido(array $r): ?string
{
    if (empty($r['faq_id'])) {
        return null;
    }
    foreach (faqsPrueba() as $f) {
        if ((int) $f['id'] === (int) $r['faq_id']) {
            return (string) $f['slug'];
        }
    }
    return null;
}

function idPorSlug(string $slug): int
{
    foreach (faqsPrueba() as $f) {
        if ($f['slug'] === $slug) {
            return (int) $f['id'];
        }
    }
    throw new \RuntimeException('Slug inexistente en el fixture: ' . $slug);
}

/** Slugs de las sugerencias, en orden. @return string[] */
function slugsSugeridos(array $r): array
{
    $porId = [];
    foreach (faqsPrueba() as $f) {
        $porId[(int) $f['id']] = (string) $f['slug'];
    }
    return array_map(static function ($s) use ($porId) {
        return $porId[(int) $s['id']] ?? '?';
    }, $r['sugerencias']);
}

/** Restaura la configuración de pruebas (deshace Config::establecer). */
function restaurarConfig(): void
{
    Config::cargar();
}

/** Crea un archivo temporal con el contenido dado y devuelve su ruta (se borra al terminar). */
function archivoTemporal(string $contenido, string $ext = '.csv'): string
{
    $ruta = tempnam(sys_get_temp_dir(), 'bfp');
    if ($ruta === false) {
        throw new \RuntimeException('No se pudo crear un archivo temporal');
    }
    $ruta2 = $ruta . $ext;
    rename($ruta, $ruta2);
    file_put_contents($ruta2, $contenido);
    register_shutdown_function(static function () use ($ruta2) {
        if (is_file($ruta2)) {
            unlink($ruta2);
        }
    });
    return $ruta2;
}

/** Eventos futuros del fixture de calendario (misma regla que el motor: fecha >= hoy). @return array<int,array<string,mixed>> */
function eventosFuturosFixture(): array
{
    $CAL = null;
    require __DIR__ . '/../fixtures/site_data/calendario_data.php';
    $ev = $CAL['eventos'];
    usort($ev, static function ($a, $b) {
        return strcmp($a['fecha'], $b['fecha']);
    });
    $hoy = date('Y-m-d');
    return array_values(array_filter($ev, static function ($e) use ($hoy) {
        return $e['fecha'] >= $hoy;
    }));
}
