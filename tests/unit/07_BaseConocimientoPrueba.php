<?php
declare(strict_types=1);

use Buscador\Search\Buscador;
use Buscador\Search\Csv;
use Buscador\Search\Dinamicas;
use Buscador\Search\Indice;

grupo('Base de conocimiento real (conocimiento/*.csv)');

function rutaConocimiento(string $archivo): string
{
    return __DIR__ . '/../../conocimiento/' . $archivo;
}

prueba('preguntas.csv tiene exactamente las columnas esperadas', function () {
    $fh = fopen(rutaConocimiento('preguntas.csv'), 'rb');
    $enc = fgetcsv($fh, 0, ',', '"', '\\');
    fclose($fh);
    $enc[0] = (string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $enc[0]);
    igual(Csv::COLUMNAS_FAQ, array_map('trim', $enc));
});

prueba('areas.csv tiene exactamente las columnas esperadas', function () {
    $fh = fopen(rutaConocimiento('areas.csv'), 'rb');
    $enc = fgetcsv($fh, 0, ',', '"', '\\');
    fclose($fh);
    $enc[0] = (string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $enc[0]);
    igual(Csv::COLUMNAS_AREA, array_map('trim', $enc));
});

prueba('cada fila es válida: slug único, tipo, handler, estado, área y fuente', function () {
    $areas = array_column(Csv::leer(rutaConocimiento('areas.csv')), 'clave');
    $vistos = [];
    $errores = [];
    foreach (Csv::leer(rutaConocimiento('preguntas.csv')) as $i => $f) {
        $fila = 'fila ' . ($i + 2) . ' (' . $f['slug'] . ')';
        if (!preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $f['slug'])) {
            $errores[] = "$fila: slug inválido";
        }
        if (isset($vistos[$f['slug']])) {
            $errores[] = "$fila: slug repetido";
        }
        $vistos[$f['slug']] = true;
        if (trim($f['pregunta']) === '' || trim($f['categoria']) === '') {
            $errores[] = "$fila: falta pregunta o categoría";
        }
        $tipo = $f['tipo'] ?: 'fija';
        if (!in_array($tipo, ['fija', 'dinamica'], true)) {
            $errores[] = "$fila: tipo '$tipo' inválido";
        }
        if ($tipo === 'dinamica' && !isset(Dinamicas::HANDLERS[$f['handler']])) {
            $errores[] = "$fila: handler '{$f['handler']}' inexistente";
        }
        if ($tipo === 'fija' && trim($f['respuesta']) === '') {
            $errores[] = "$fila: respuesta fija vacía";
        }
        if (!in_array($f['estado'] ?: 'borrador', ['borrador', 'validada', 'archivada'], true)) {
            $errores[] = "$fila: estado '{$f['estado']}' inválido";
        }
        if ($f['area'] !== '' && !in_array($f['area'], $areas, true)) {
            $errores[] = "$fila: área '{$f['area']}' no existe en areas.csv";
        }
        if (!preg_match('#^https?://#', $f['fuente_url'])) {
            $errores[] = "$fila: fuente_url debe ser una URL";
        }
        if ($f['estado'] === 'validada' && ($f['validado_por'] === '' || $f['validado_en'] === '')) {
            $errores[] = "$fila: validada sin validado_por/validado_en";
        }
    }
    igual([], $errores);
});

prueba('autoconsistencia: al menos 90% de las preguntas se encuentran a sí mismas', function () {
    $faqs = Csv::faqsEnMemoria(rutaConocimiento('preguntas.csv'), rutaConocimiento('areas.csv'));
    $m = new Buscador(new Indice($faqs));
    $fallos = [];
    foreach ($faqs as $f) {
        $r = $m->buscar((string) $f['pregunta'], ['carrera' => $f['carrera']]);
        $primera = $r['faq_id'] ?? ($r['sugerencias'][0]['id'] ?? null);
        if ((int) $primera !== (int) $f['id']) {
            $fallos[] = $f['slug'] . ' → ' . $r['resultado'];
        }
    }
    $pct = $faqs ? 100 * (count($faqs) - count($fallos)) / count($faqs) : 0;
    afirmar($pct >= 90, sprintf('%.0f%% (%d de %d). No se encuentran: %s', $pct, count($faqs) - count($fallos), count($faqs), implode('; ', $fallos)));
});
