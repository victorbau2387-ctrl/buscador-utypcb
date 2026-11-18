<?php
// Prueba rápida del motor sin base de datos:  php private/bin/probar.php "cuanto cuesta la inscripcion"
// Usa conocimiento/*.csv. Con --ctx=slug simula el contexto de una respuesta anterior.
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit; }
putenv('BF_CONFIG=' . (getenv('BF_CONFIG') ?: __DIR__ . '/../../tests/config.pruebas.php'));
require __DIR__ . '/../src/bootstrap.php';

use Buscador\Search\{Buscador, Csv, Indice};

$raiz = dirname(__DIR__, 2);
$ctx = null;
$consultas = [];
foreach (array_slice($argv, 1) as $a) {
    if (strpos($a, '--ctx=') === 0) { $ctx = substr($a, 6); } else { $consultas[] = $a; }
}
$motor = new Buscador(new Indice(Csv::faqsEnMemoria($raiz . '/conocimiento/preguntas.csv', $raiz . '/conocimiento/areas.csv')));
foreach ($consultas as $q) {
    $r = $motor->buscar($q, ['carrera' => $ctx]);
    $ctx = $r['contexto']['carrera'];
    printf("\n> %s\n  %s (conf %.2f, %d ms) términos=[%s] carrera=%s%s\n", $q, $r['resultado'], $r['confianza'], $r['ms'],
        implode(' ', $r['diagnostico']['terminos'] ?? []), $ctx ?? '-', !empty($r['diagnostico']['uso_contexto']) ? ' (contexto)' : '');
    if ($r['respuesta']) { echo '  ' . str_replace("\n", "\n  ", $r['respuesta']['pregunta'] . "\n" . $r['respuesta']['texto']) . "\n"; }
    foreach ($r['sugerencias'] as $s) { printf("  ¿%s? (%.2f)\n", trim($s['pregunta'], '¿?'), $s['confianza']); }
    if ($r['canalizar']) { echo '  → Canalizar a ' . $r['canalizar']['nombre'] . "\n"; }
}
