<?php
declare(strict_types=1);

use Buscador\Search\Dinamicas;
use Buscador\Site\DatosSitio;

grupo('Dinamicas');

/** Fecha en el formato que usa el motor: "14 de diciembre de 2026". */
function fechaLarga(string $ymd): string
{
    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $t = strtotime($ymd);
    return (int) date('j', $t) . ' de ' . $meses[(int) date('n', $t) - 1] . ' de ' . date('Y', $t);
}

function dinamica(string $handler, string $consulta = '', ?string $carrera = null, string $intro = ''): array
{
    return Dinamicas::responder(['handler' => $handler, 'respuesta' => $intro], $consulta, $carrera);
}

const RE_LINEA_FECHA = '/^• \d{1,2} de (enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre) de \d{4}: .+$/u';

prueba('todos los handlers declarados devuelven texto y enlaces', function () {
    foreach (array_keys(Dinamicas::HANDLERS) as $h) {
        $r = dinamica($h, 'consulta', null);
        afirmar(is_string($r['texto']) && $r['texto'] !== '', "$h: texto vacío");
        afirmar(is_array($r['enlaces']), "$h: enlaces");
        foreach ($r['enlaces'] as $e) {
            coincide('#^https://#', $e['url'], "$h: URL absoluta");
            afirmar($e['titulo'] !== '', "$h: título del enlace");
        }
    }
});

prueba('carreras_lista: lista las 5 carreras con su TSU', function () {
    $r = dinamica('carreras_lista');
    $lineas = explode("\n", $r['texto']);
    igual(6, count($lineas), 'encabezado + 5 carreras');
    igual('• Ingeniería Civil (Técnico Superior Universitario en Construcción)', $lineas[1]);
    igual('https://utypcb.edu.mx/plan_estudios.php', $r['enlaces'][0]['url']);
});

prueba('carreras_lista: usa la introducción de la pregunta si existe', function () {
    $r = dinamica('carreras_lista', '', null, 'Nuestra oferta:');
    igual(0, strpos($r['texto'], "Nuestra oferta:\n"));
});

prueba('carrera_detalle: con carrera muestra nombre, TSU, nivel y tríptico', function () {
    $r = dinamica('carrera_detalle', '', SLUG_CIVIL);
    igual("Ingeniería Civil\nIncluye: Técnico Superior Universitario en Construcción.\nNivel: TSU y licenciatura.", $r['texto']);
    igual(2, count($r['enlaces']));
    igual('https://utypcb.edu.mx/docs/tripticos/civil.pdf', $r['enlaces'][1]['url']);
});

prueba('carrera_detalle: tríptico enlazado por título cuando el slug no coincide', function () {
    $r = dinamica('carrera_detalle', '', SLUG_COMERCIO);
    igual('https://utypcb.edu.mx/docs/tripticos/comercio.pdf', $r['enlaces'][1]['url'] ?? null);
});

prueba('carrera_detalle: sin carrera pregunta cuál y lista las disponibles', function () {
    $r = dinamica('carrera_detalle', '', null);
    contiene('¿De qué carrera quieres información?', $r['texto']);
    contiene('• Ingeniería Civil', $r['texto']);
});

prueba('calendario_proximos: hasta 5 eventos futuros del fixture, en orden y con fecha legible', function () {
    $r = dinamica('calendario_proximos');
    $futuros = array_slice(eventosFuturosFixture(), 0, 5);
    igual('https://utypcb.edu.mx/calendario.php', $r['enlaces'][0]['url']);
    if (!$futuros) {
        igual('No hay próximos eventos publicados en el calendario.', $r['texto']);
        return;
    }
    $lineas = explode("\n", $r['texto']);
    igual('Próximas fechas del calendario escolar:', array_shift($lineas));
    igual(count($futuros), count($lineas));
    foreach ($futuros as $i => $e) {
        coincide(RE_LINEA_FECHA, $lineas[$i]);
        igual('• ' . fechaLarga($e['fecha']) . ': ' . $e['titulo'], $lineas[$i]);
    }
});

prueba('calendario_proximos: nunca muestra eventos pasados', function () {
    $r = dinamica('calendario_proximos');
    $hoy = date('Y-m-d');
    $CAL = null;
    require __DIR__ . '/../fixtures/site_data/calendario_data.php';
    foreach ($CAL['eventos'] as $e) {
        if ($e['fecha'] < $hoy) {
            noContiene(fechaLarga($e['fecha']) . ': ' . $e['titulo'], $r['texto'], 'evento pasado');
        }
    }
});

prueba('calendario_evento: próxima fecha del evento mencionado (vacaciones)', function () {
    $r = dinamica('calendario_evento', '¿cuándo son las vacaciones?');
    $vac = array_values(array_filter(eventosFuturosFixture(), static function ($e) {
        return $e['tipo'] === 'vacaciones';
    }));
    if ($vac) {
        igual($vac[0]['titulo'] . ': ' . fechaLarga($vac[0]['fecha']) . '.', $r['texto']);
    } else {
        contiene('No encontré ese evento', $r['texto']);
    }
});

prueba('calendario_evento: evaluaciones finales', function () {
    $r = dinamica('calendario_evento', 'evaluaciones finales');
    $ev = array_values(array_filter(eventosFuturosFixture(), static function ($e) {
        return $e['titulo'] === 'Evaluaciones finales';
    }));
    if ($ev) {
        igual('Evaluaciones finales: ' . fechaLarga($ev[0]['fecha']) . '.', $r['texto']);
    } else {
        contiene('No encontré ese evento', $r['texto']);
    }
});

prueba('calendario_evento: evento inexistente → lo dice y muestra próximas fechas', function () {
    $r = dinamica('calendario_evento', 'xilofono');
    contiene('No encontré ese evento en el calendario publicado.', $r['texto']);
});

prueba('noticias_recientes: las 3 más recientes primero, con resumen', function () {
    $r = dinamica('noticias_recientes');
    $lineas = explode("\n", $r['texto']);
    igual(4, count($lineas));
    igual('• 8 de junio de 2026: Felicitación al Mtro. Salvador Herrera Soriano — La comunidad universitaria felicita al Director del Tecnológico Nacional de México.', $lineas[1]);
    contiene('7 de junio de 2026', $lineas[2]);
    contiene('6 de junio de 2026', $lineas[3]);
    igual('https://utypcb.edu.mx/noticias.php', $r['enlaces'][0]['url']);
});

prueba('deportes_recientes: 3 encuentros con su categoría', function () {
    $r = dinamica('deportes_recientes');
    $lineas = explode("\n", $r['texto']);
    igual(4, count($lineas));
    foreach (array_slice($lineas, 1) as $l) {
        coincide('/^• 25 de septiembre de 2025: .+ — .+$/u', $l);
    }
    noContiene('2024', $r['texto'], 'el encuentro más antiguo queda fuera');
});

prueba('triptico_carrera: con carrera, solo su PDF', function () {
    $r = dinamica('triptico_carrera', '', SLUG_ITI);
    igual(1, count($r['enlaces']));
    igual('https://utypcb.edu.mx/docs/tripticos/iti.pdf', $r['enlaces'][0]['url']);
});

prueba('triptico_carrera: sin carrera, todos los trípticos', function () {
    $r = dinamica('triptico_carrera', '', null);
    igual('Estos son los trípticos disponibles:', $r['texto']);
    igual(5, count($r['enlaces']));
});

prueba('handler desconocido devuelve la respuesta fija recortada', function () {
    igual(['texto' => 'hola', 'enlaces' => []], Dinamicas::responder(['handler' => 'no_existe', 'respuesta' => ' hola '], '', null));
});

prueba('DatosSitio::url: rutas relativas y absolutas', function () {
    igual('https://utypcb.edu.mx/calendario.php', DatosSitio::url('calendario.php'));
    igual('https://utypcb.edu.mx/docs/x.pdf', DatosSitio::url('/docs/x.pdf'));
    igual('https://otro.mx/a', DatosSitio::url('https://otro.mx/a'));
});
