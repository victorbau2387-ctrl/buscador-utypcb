<?php
declare(strict_types=1);

use Buscador\Support\Config;

grupo('Buscador');

const CLAVES_RESULTADO = ['resultado', 'respuesta', 'sugerencias', 'canalizar', 'contexto', 'confianza', 'faq_id', 'diagnostico', 'ms'];

prueba('forma de la salida: siempre las mismas claves', function () {
    $m = motorPrueba();
    foreach (['cuanto cuesta la inscripcion', 'examen', 'asdfgh qwerty', 'tienen enfermeria', ''] as $q) {
        $r = $m->buscar($q);
        foreach (CLAVES_RESULTADO as $k) {
            afirmar(array_key_exists($k, $r), "falta '$k' para " . exportar($q));
        }
        enArreglo($r['resultado'], ['respondida', 'sugerencias', 'sin_respuesta', 'no_ofertada']);
        afirmar(is_int($r['ms']) && $r['ms'] >= 0, 'ms');
    }
});

prueba('respondida: pregunta clara con faltas y abreviaturas', function () {
    $m = motorPrueba();
    $casos = [
        'cuanto cuesta la inscripcion'     => 'inscripcion-costo',
        '¿Cuánto cuesta la inscripción?'   => 'inscripcion-costo',
        'precio de la inscripsion'         => 'inscripcion-costo',
        'q carreras tienen'                => 'carreras-oferta',
        'kiero una veca'                   => 'becas-tipos',
        'cuando son las bacaciones'        => 'calendario-vacaciones',
        'ke rekisitos piden para la fixa'  => 'admision-requisitos',
        'ultimas noticias'                 => 'noticias',
        'hay servicio de enfermeria'       => 'servicio-medico',
    ];
    foreach ($casos as $q => $slug) {
        $r = $m->buscar($q);
        igual('respondida', $r['resultado'], $q);
        igual($slug, slugRespondido($r), $q);
        afirmar($r['confianza'] >= (float) Config::get('busqueda.umbral_respuesta'), "$q: confianza bajo el umbral");
        igual($r['faq_id'], $r['respuesta']['id'], $q);
    }
});

prueba('respondida: incluye texto, fuente, área y como máximo 2 relacionadas', function () {
    $r = motorPrueba()->buscar('cuanto cuesta la inscripcion');
    igual('¿Cuánto cuesta la inscripción?', $r['respuesta']['pregunta']);
    igual('Servicios Escolares', $r['respuesta']['area']);
    coincide('#^https://#', $r['respuesta']['fuente']);
    igual(false, $r['respuesta']['validada'], 'estado borrador');
    afirmar(count($r['sugerencias']) <= 2, 'relacionadas');
    igual(null, $r['canalizar']);
});

prueba('respondida: dinámica arma el texto con los datos del sitio', function () {
    $r = motorPrueba()->buscar('que carreras hay');
    igual('carreras-oferta', slugRespondido($r));
    contiene('• Ingeniería Civil', $r['respuesta']['texto']);
    igual('https://utypcb.edu.mx/plan_estudios.php', $r['respuesta']['enlaces'][0]['url']);
});

prueba('sugerencias: consulta ambigua ofrece hasta 3 opciones', function () {
    $m = motorPrueba();
    $r = $m->buscar('examen');
    igual('sugerencias', $r['resultado']);
    igual(null, $r['respuesta']);
    igual(null, $r['faq_id']);
    afirmar(count($r['sugerencias']) >= 2 && count($r['sugerencias']) <= 3, 'entre 2 y 3 sugerencias');
    enArreglo('calendario-evaluaciones', slugsSugeridos($r));
    $r = $m->buscar('fechas');
    igual('sugerencias', $r['resultado']);
    igual('calendario-proximos', slugsSugeridos($r)[0]);
});

prueba('sugerencias: ordenadas por confianza descendente', function () {
    $r = motorPrueba()->buscar('fechas');
    $prev = 2.0;
    foreach ($r['sugerencias'] as $s) {
        afirmar($s['confianza'] <= $prev, 'orden');
        afirmar($s['pregunta'] !== '', 'texto de la sugerencia');
        $prev = $s['confianza'];
    }
});

prueba('sin_respuesta: texto sin relación canaliza al contacto general', function () {
    $r = motorPrueba()->buscar('asdfgh qwerty');
    igual('sin_respuesta', $r['resultado']);
    igual(null, $r['respuesta']);
    igual([], $r['sugerencias']);
    igual('Servicios Escolares', $r['canalizar']['nombre']);
    igual('https://utypcb.edu.mx/contacto.php', $r['canalizar']['url']);
});

prueba('sin_respuesta: solo saludos o palabras vacías', function () {
    $m = motorPrueba();
    foreach (['hola buenas tardes', 'gracias', '???', ''] as $q) {
        $r = $m->buscar($q);
        igual('sin_respuesta', $r['resultado'], exportar($q));
        afirmar(is_array($r['canalizar']), 'canaliza');
    }
});

prueba('sin_respuesta: un intento de XSS no produce coincidencias ni se refleja', function () {
    $r = motorPrueba()->buscar('<script>alert(1)</script>');
    igual('sin_respuesta', $r['resultado']);
    noContiene('<script', json_encode($r, JSON_UNESCAPED_UNICODE), 'la salida del motor no repite la consulta');
});

prueba('no_ofertada: carrera que no se imparte, con la lista de carreras disponibles', function () {
    $r = motorPrueba()->buscar('¿tienen enfermería?');
    igual('no_ofertada', $r['resultado']);
    igual(1.0, $r['confianza']);
    contiene('no ofrece la carrera de Enfermería', $r['respuesta']['texto']);
    contiene('• Ingeniería Civil', $r['respuesta']['texto']);
    igual('https://utypcb.edu.mx/plan_estudios.php', $r['respuesta']['fuente']);
});

prueba('no_ofertada: "servicio de enfermería" se responde como servicio, no como carrera', function () {
    $r = motorPrueba()->buscar('¿hay servicio de enfermería?');
    distinto('no_ofertada', $r['resultado']);
    igual('servicio-medico', slugRespondido($r));
});

prueba('no_ofertada: si se menciona una carrera activa, gana la activa', function () {
    $r = motorPrueba()->buscar('triptico de ingenieria civil');
    distinto('no_ofertada', $r['resultado']);
    igual(SLUG_CIVIL, $r['contexto']['carrera']);
});

prueba('carrera: la detectada viaja en contexto y se usa en la respuesta dinámica', function () {
    $r = motorPrueba()->buscar('triptico de sistemas');
    igual('tripticos', slugRespondido($r));
    igual(SLUG_ITI, $r['contexto']['carrera']);
    igual('https://utypcb.edu.mx/docs/tripticos/iti.pdf', $r['respuesta']['enlaces'][0]['url']);
});

prueba('contexto: "información de la carrera de civil" → "y el tríptico?" entrega el de Civil', function () {
    $m = motorPrueba();
    $r1 = $m->buscar('informacion de la carrera de civil');
    igual('carrera-detalle', slugRespondido($r1));
    igual(SLUG_CIVIL, $r1['contexto']['carrera']);
    $r2 = $m->buscar('y el tríptico?', $r1['contexto']);
    igual('respondida', $r2['resultado']);
    igual('tripticos', slugRespondido($r2));
    igual(true, $r2['diagnostico']['uso_contexto']);
    igual('https://utypcb.edu.mx/docs/tripticos/civil.pdf', $r2['respuesta']['enlaces'][0]['url']);
});

prueba('contexto: "info de civil" → "y el tríptico?" (consulta corta solo con la carrera)', function () {
        $m = motorPrueba();
        $r1 = $m->buscar('info de civil');
        igual(SLUG_CIVIL, $r1['contexto']['carrera']);
        igual('carrera-detalle', slugRespondido($r1), 'primer mensaje');
    }
);

prueba('contexto: "info de civil" conserva la carrera para el siguiente mensaje', function () {
    $m = motorPrueba();
    $r1 = $m->buscar('info de civil');
    igual(SLUG_CIVIL, $r1['contexto']['carrera']);
    $r2 = $m->buscar('y el tríptico?', $r1['contexto']);
    igual('tripticos', slugRespondido($r2));
    igual('https://utypcb.edu.mx/docs/tripticos/civil.pdf', $r2['respuesta']['enlaces'][0]['url']);
});

prueba('contexto: una carrera en contexto no debe ocultar una carrera no ofertada', function () {
        $r = motorPrueba()->buscar('¿tienen enfermería?', ['carrera' => SLUG_CIVIL]);
        igual('no_ofertada', $r['resultado']);
    }
);

prueba('contexto: una pregunta larga y completa no usa el contexto', function () {
    $r = motorPrueba()->buscar('cuales son las fechas de evaluaciones finales y extraordinarias', ['carrera' => SLUG_CIVIL]);
    igual(false, $r['diagnostico']['uso_contexto']);
    igual(null, $r['contexto']['carrera']);
});

prueba('contexto: valores inválidos se ignoran', function () {
    $m = motorPrueba();
    foreach (['no-existe', '', 123, ['x'], null] as $ctx) {
        $r = $m->buscar('y el triptico?', ['carrera' => $ctx]);
        igual(null, $r['contexto']['carrera'], exportar($ctx));
        igual(false, $r['diagnostico']['uso_contexto']);
    }
});

prueba('umbrales: subir umbral_respuesta convierte la respuesta en sugerencias', function () {
    Config::establecer(['busqueda' => ['umbral_respuesta' => 1.01]]);
    try {
        $r = motorPrueba()->buscar('cuanto cuesta la inscripcion');
        igual('sugerencias', $r['resultado']);
        igual('inscripcion-costo', slugsSugeridos($r)[0]);
    } finally {
        restaurarConfig();
    }
});

prueba('umbrales: subir umbral_sugerencias deja la consulta sin respuesta', function () {
    Config::establecer(['busqueda' => ['umbral_sugerencias' => 0.99]]);
    try {
        $r = motorPrueba()->buscar('examen');
        igual('sin_respuesta', $r['resultado']);
        afirmar(is_array($r['canalizar']), 'canaliza');
        igual('Servicios Escolares', $r['canalizar']['nombre'], 'contacto del área de la mejor coincidencia');
    } finally {
        restaurarConfig();
    }
});

prueba('umbrales: max_sugerencias limita la lista', function () {
    Config::establecer(['busqueda' => ['max_sugerencias' => 1]]);
    try {
        $r = motorPrueba()->buscar('fechas');
        igual('sugerencias', $r['resultado']);
        igual(1, count($r['sugerencias']));
    } finally {
        restaurarConfig();
    }
});

prueba('umbrales: empate cercano por debajo de 0.85 no responde directo', function () {
    // "examen" queda casi empatado entre evaluaciones y requisitos (< 0.04 de diferencia).
    $r = motorPrueba()->buscar('examen');
    igual('sugerencias', $r['resultado']);
});

prueba('ver(): respuesta completa de una pregunta elegida', function () {
    $m = motorPrueba();
    $r = $m->ver(idPorSlug('becas-tipos'));
    igual('respondida', $r['resultado']);
    igual('¿Qué becas puedo solicitar?', $r['respuesta']['pregunta']);
    igual(null, $m->ver(99999));
});

prueba('ver(): una pregunta dinámica usa la carrera del contexto', function () {
    $r = motorPrueba()->ver(idPorSlug('tripticos'), ['carrera' => SLUG_ENERGIA]);
    igual('https://utypcb.edu.mx/docs/tripticos/energia.pdf', $r['respuesta']['enlaces'][0]['url']);
    igual(SLUG_ENERGIA, $r['contexto']['carrera']);
});

prueba('rendimiento: cada búsqueda tarda menos de 100 ms', function () {
    $m = motorPrueba();
    foreach (['cuanto cuesta la inscripcion', 'kiero una veca', 'tienen enfermeria', 'asdfgh qwerty uiop', 'fechas'] as $q) {
        $r = $m->buscar($q);
        afirmar($r['ms'] < 100, "$q tardó {$r['ms']} ms");
    }
});
